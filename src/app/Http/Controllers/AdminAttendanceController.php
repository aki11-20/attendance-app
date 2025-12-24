<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCorrectionRequest;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\CorrectionRequest;
use Illuminate\Support\Facades\DB;

class AdminAttendanceController extends Controller
{
    public function index(Request $request) {
        $dateParam = $request->query('date');
        $target = $dateParam ? Carbon::createFromFormat('Y-m-d', $dateParam) : now();

        $workDate = $target->toDateString();

        $attendances = Attendance::with('user')
        ->where('work_date', $workDate)
        ->orderBy('user_id')
        ->get();

        return view('admin.attendance-list', compact('attendances', 'target'));
    }

    public function detail($attendanceId) {
        $attendance = Attendance::with(['user', 'breaks', 'correctionRequests'])
        ->findOrFail($attendanceId);

        $latest = $attendance->correctionRequests()->latest()->first();
        if ($latest && $latest->status === CorrectionRequest::STATUS_PENDING) {
            return redirect()->route('admin.request.show', ['id' => $latest->id]);
        }

        $date = $attendance->work_date instanceof Carbon ? $attendance->work_date : Carbon::parse($attendance->work_date);

        $display = [
            'clock_in' => $attendance->clock_in ? $attendance->clock_in->format('H:i') : '',
            'clock_out' => $attendance->clock_out ? $attendance->clock_out->format('H:i') : '',
            'breaks' => $attendance->breaks->map(function ($breakTime) {
                return [
                    'start' => $breakTime->break_start ? $breakTime->break_start->format('H:i') : '',
                    'end' => $breakTime->break_end ? $breakTime->break_end->format('H:i') : '',
                ];
            })->toArray(),
            'comment' => $attendance->note ?? '',
        ];

        return view('attendance.detail', [
            'mode' => 'admin',
            'attendance' => $attendance,
            'date' => $date,
            'display' => $display,
            'isPending' => false,
            'isApprovalScreen' => false,
            'isApproved' => false,
            'requestId' => null,
        ]);
    }

    public function update(StoreCorrectionRequest $request, $attendanceId) {
        $attendance = Attendance::with('breaks')->findOrFail($attendanceId);
        $data = $request->validated();

        $workDate = $attendance->work_date instanceof Carbon ? $attendance->work_date : Carbon::parse($attendance->work_date);

        $clockIn = !empty($data['clock_in']) ? Carbon::parse($workDate->format('Y-m-d') . ' ' . $data['clock_in']) : null;
        $clockOut = !empty($data['clock_out']) ? Carbon::parse($workDate->format('Y-m-d') . ' ' . $data['clock_out']) : null;

        $breaks = [];
        foreach (($data['breaks'] ?? []) as $row) {
            if (empty($row['start']) && empty($row['end'])) {
                continue;
            }

            $breaks[] = [
                'start' => !empty($row['start']) ? Carbon::parse($workDate->format('Y-m-d') . ' ' . $row['start']) : null,
                'end' => !empty($row['end']) ? Carbon::parse($workDate->format('Y-m-d') . ' ' . $row['end']) : null,
            ];
        }

        DB::transaction(function () use ($attendance, $clockIn, $clockOut, $breaks, $data) {
            $attendance->clock_in = $clockIn;
            $attendance->clock_out = $clockOut;
            $attendance->note = $data['comment'];
            $attendance->breaks()->delete();
            $breakNumber = 1;
            foreach ($breaks as $row) {
                $attendance->breaks()->create([
                    'break_no' => $breakNumber++,
                    'break_start' => $row['start'],
                    'break_end' => $row['end'],
                ]);
            }

            $attendance->load('breaks');

            if ($attendance->clock_in && $attendance->clock_out) {
                $totalWorkMinutes = Carbon::parse($attendance->clock_in)->seconds(0)
                    ->diffInMinutes(Carbon::parse($attendance->clock_out)->seconds(0));

                $totalBreakMinutes = 0;
                foreach ($attendance->breaks as $breakTime) {
                    if ($breakTime->break_start && $breakTime->break_end) {
                        $totalBreakMinutes += Carbon::parse($breakTime->break_start)->seconds(0)
                            ->diffInMinutes(Carbon::parse($breakTime->break_end)->seconds(0));
                    }
                }
                $attendance->total_break_minutes = $totalBreakMinutes;
                $attendance->total_work_minutes = max($totalWorkMinutes - $totalBreakMinutes, 0);
            }
            $attendance->save();
        });

        return redirect()->route('admin.attendance.detail', ['id' => $attendance->id])
            ->with('message', '修正しました。');
    }

    public function staffMonthly(Request $request, $userId) {
        $user = User::findOrFail($userId);

        $monthParam = $request->query('month');
        $target = $monthParam ? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth() : now()->startOfMonth();

        $startOfMonth = $target->copy()->startOfMonth();
        $endOfMonth = $target->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', $user->id)
        ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
        ->get()
        ->keyBy(function ($attendance) {
            return $attendance->work_date->toDateString();
        });

        $days = [];

        foreach (CarbonPeriod::create($startOfMonth, $endOfMonth) as $date) {
            $key = $date->toDateString();
            $attendance = $attendances->get($key);

            $days[] = [
                'date' => $date,
                'in' => $attendance && $attendance->clock_in ? $attendance->clock_in->format('H:i') : '',
                'out' => $attendance && $attendance->clock_out ? $attendance->clock_out->format('H:i') : '',
                'break' => $attendance && $attendance->total_break_minutes !== null ? $this->formatMinutes($attendance->total_break_minutes) : '',
                'total' => $attendance && $attendance->total_work_minutes !== null ? $this->formatMinutes($attendance->total_work_minutes) : '',
                'id' => $attendance ? $attendance->id : null,
            ];
        }

        $currentMonthLabel = $target->format('Y/m');
        $prevMonthParam = $target->copy()->subMonth()->format('Y-m');
        $nextMonthParam = $target->copy()->addMonth()->format('Y-m');

        return view('admin.attendance-staff-monthly', compact(
            'user',
            'days',
            'currentMonthLabel',
            'prevMonthParam',
            'nextMonthParam'
        ));
    }

    public function exportStaffMonthlyCsv(Request $request, $userId) {
        $user = User::findOrFail($userId);

        $monthParam = $request->query('month');
        $target = $monthParam ? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth() : now()->startOfMonth();

        $startOfMonth = $target->copy()->startOfMonth();
        $endOfMonth = $target->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', $user->id)
        ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
        ->get()
        ->keyBy(function ($attendance) {
            return $attendance->work_date->toDateString();
        });

        $fileName = sprintf('attendance_%s_%s.csv', str_replace(' ', '_', $user->name), $target->format('Y-m'));

        return response()->streamDownload(function () use ($attendances, $startOfMonth, $endOfMonth) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

            foreach (CarbonPeriod::create($startOfMonth, $endOfMonth) as $date) {
                $dateKey = $date->toDateString();
                $attendance = $attendances->get($dateKey);

                $clockIn = ($attendance && $attendance->clock_in) ? $attendance->clock_in->format('H:i') : '';
                $clockOut = ($attendance && $attendance->clock_out) ? $attendance->clock_out->format('H:i') : '';

                $breakTime = ($attendance && $attendance->total_break_minutes !== null) ? $this->formatMinutes($attendance->total_break_minutes) : '';

                $totalTime = ($attendance && $attendance->total_work_minutes !== null) ? $this->formatMinutes($attendance->total_work_minutes) : '';

                fputcsv($handle, [
                    $date->format('Y/m/d'),
                    $clockIn,
                    $clockOut,
                    $breakTime,
                    $totalTime,
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function formatMinutes($minutes) {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        return sprintf('%d:%02d', $hours, $mins);
    }
}
