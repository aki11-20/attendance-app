<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\CorrectionRequest;

class AttendanceController extends Controller
{
    public function index() {
        $user = Auth::user();
        $today = now()->toDateString();

        $attendance = Attendance::with('breaks')
        ->where('user_id', $user->id)
        ->where('work_date', $today)
        ->first();

        $status = $attendance ? $attendance->status : '勤務外';
        $date = now()->translatedFormat('Y年n月j日(D)');
        $time = now()->format('H:i');

        return view('attendance.index', compact('date', 'time', 'status'));
    }

    public function clockIn(Request $request) {
        $user = Auth::user();
        $today = now()->toDateString();

        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            ['clock_in' => null, 'clock_out' => null]
        );
        if ($attendance->clock_in) {
            return redirect()->route('attendance.index')
            ->with('message', '本日の出勤は登録済です。');
        }

        $attendance->clock_in = now();
        $attendance->status = Attendance::STATUS_WORKING;
        $attendance->save();

        return redirect()->route('attendance.index');
    }

    public function breakStart(Request $request) {
        $user = Auth::user();
        $today = now()->toDateString();

        $attendance = Attendance::with('breaks')
        ->where('user_id', $user->id)
        ->where('work_date', $today)
        ->first();

        if (!$attendance || !$attendance->clock_in || $attendance->clock_out) {
            return redirect()->route('attendance.index');
        }
        $openBreak = $attendance
        ->breaks()
        ->whereNull('break_end')
        ->first();
        if ($openBreak) {
            return redirect()->route('attendance.index');
        }

        $currentMaxNo = $attendance->breaks()->max('break_no');
        $nextBreakNo = is_null($currentMaxNo) ? 1 : $currentMaxNo + 1;

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_no' => $nextBreakNo,
            'break_start' => now(),
        ]);

        $attendance->status = Attendance::STATUS_BREAKING;
        $attendance->save();

        return redirect()->route('attendance.index');
    }

    public function breakEnd(Request $request) {
        $user = Auth::user();
        $today = now()->toDateString();

        $attendance = Attendance::with('breaks')
        ->where('user_id', $user->id)
        ->where('work_date', $today)
        ->first();

        if (!$attendance || !$attendance->clock_in || $attendance->clock_out) {
            return redirect()->route('attendance.index');
        }

        $openBreak = $attendance
        ->breaks()
        ->whereNull('break_end')
        ->latest()
        ->first();

        if (!$openBreak) {
            return redirect()->route('attendance.index');
        }

        $openBreak->break_end = now();
        $openBreak->save();

        $attendance->status = Attendance::STATUS_WORKING;
        $attendance->save();

        return redirect()->route('attendance.index');
    }

    public function clockOut(Request $request) {
        $user = Auth::user();
        $today = now()->toDateString();

        $attendance = Attendance::with('breaks')
        ->where('user_id', $user->id)
        ->where('work_date', $today)
        ->first();

        if (!$attendance || !$attendance->clock_in || $attendance->clock_out) {
            return redirect()->route('attendance.index');
        }

        $attendance->clock_out = now();

        $totalBreakMinutes = 0;

        foreach ($attendance->breaks as $break) {
            if ($break->break_start &&$break->break_end) {
                $start = Carbon::parse($break->break_start)->seconds(0);
                $end = Carbon::parse($break->break_end)->seconds(0);

                $totalBreakMinutes += $start->diffInMinutes($end);
            }
        }

        $clockIn = Carbon::parse($attendance->clock_in)->seconds(0);
        $clockOut = Carbon::parse($attendance->clock_out)->seconds(0);

        $totalWorkMinutes = $clockIn->diffInMinutes($clockOut);

        $netWorkMinutes = max($totalWorkMinutes - $totalBreakMinutes, 0);

        $attendance->total_break_minutes = $totalBreakMinutes;
        $attendance->total_work_minutes = $netWorkMinutes;
        $attendance->status = Attendance::STATUS_DONE;

        $attendance->save();

        return redirect()->route('attendance.index')->with('message', 'お疲れ様でした。');
    }

    public function monthly(Request $request) {
        $user = Auth::user();

        $monthParam = $request->query('month');
        $target = $monthParam ? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth() : now()->startOfMonth();

        $startOfMonth = $target->copy()->startOfMonth();
        $endOfMonth = $target->copy()->endOfMonth();

        $attendances = Attendance::with('breaks')
        ->where('user_id', $user->id)
        ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
        ->get()
        ->keyBy(function ($attendance) {
            return $attendance->work_date->toDateString();
        });

        $days = [];

        foreach (CarbonPeriod::create($startOfMonth, $endOfMonth) as $date) {
            $dateKey = $date->toDateString();
            $attendance = $attendances->get($dateKey);

            $breakMinutes = 0;
            if ($attendance) {
                $breakMinutes = $this->calcBreakMinutes($attendance->breaks);
            }

            $days[] = [
                'date' => $date,
                'in' => $attendance && $attendance->clock_in ? $attendance->clock_in->format('H:i') : '',
                'out' => $attendance && $attendance->clock_out ? $attendance->clock_out->format('H:i') : '',
                'break' => $attendance ? $this->formatMinutes($breakMinutes) : '',
                'total' => $attendance ? $this->formatMinutes($attendance->total_work_minutes) : '',
                'id' => $attendance ? $attendance->id : null,
            ];
        }

        $currentMonthLabel = $target->format('Y/m');
        $prevMonthParam = $target->copy()->subMonth()->format('Y-m');
        $nextMonthParam = $target->copy()->addMonth()->format('Y-m');

        return view('attendance.list', compact(
            'days',
            'currentMonthLabel',
            'prevMonthParam',
            'nextMonthParam'
        ));
    }

    private function formatMinutes($minutes) {
        $minutes = (int) ($minutes ?? 0);
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        return sprintf('%d:%02d', $hours, $mins);
    }

    private function calcBreakMinutes($breaks) {
        $totalBreakMinutes = 0;
        foreach ($breaks as $break) {
            if ($break->break_start && $break->break_end) {
                $start = Carbon::parse($break->break_start)->seconds(0);
                $end = Carbon::parse($break->break_end)->seconds(0);

                $totalBreakMinutes += $start->diffInMinutes($end);
            }
        }
        return $totalBreakMinutes;
    }

    public function detail($id) {
        $user = Auth::user();

        $attendance = Attendance::with(['user', 'breaks', 'correctionRequests'])
        ->where('user_id', $user->id)
        ->findOrFail($id);

        $date = $attendance->work_date instanceof Carbon ? $attendance->work_date : Carbon::parse($attendance->work_date);

        $latestRequest = $attendance->correctionRequests()
        ->orderByDesc('created_at')
        ->first();

        $isPending = $latestRequest && $latestRequest->status === CorrectionRequest::STATUS_PENDING;

        $editable = ! $isPending;

        $display = [
            'clock_in' => ($isPending && $latestRequest->requested_clock_in) ? Carbon::parse($latestRequest->requested_clock_in)->format('H:i') : ($attendance->clock_in ? $attendance->clock_in->format('H:i') : ''),
            'clock_out' => ($isPending && $latestRequest->requested_clock_out) ? Carbon::parse($latestRequest->requested_clock_out)->format('H:i') : ($attendance->clock_out ? $attendance->clock_out->format('H:i') : ''),
        ];

        if ($isPending && !empty($latestRequest->requested_breaks)) {
            $display['breaks'] = collect($latestRequest->requested_breaks)->map(function ($row) {
                return [
                    'start' => !empty($row['start']) ? Carbon::parse($row['start'])->format('H:i') : '',
                    'end' => !empty($row['end']) ? Carbon::parse($row['end'])->format('H:i') : '',
                ];
            })->toArray();
        } else {
            $display['breaks'] = $attendance->breaks->map(function ($b) {
                return [
                    'start' => $b->break_start ? $b->break_start->format('H:i') : '',
                    'end' => $b->break_end ? $b->break_end->format('H:i') : '',
                ];
            })->toArray();
        }

        $display['comment'] = $isPending ? ($latestRequest->comment ?? '') : ($attendance->note ?? '');

        return view('attendance.detail', [
            'mode' => 'user',
            'attendance' => $attendance,
            'date' => $date,
            'display' => $display,
            'isPending' => $isPending,
            'editable' => $editable,
            'adminCanApprove' => false,
            'adminApproved' => false,
            'formAction' => $editable ? route('stamp_requests.store', ['attendance' => $attendance->id]) : '#',
        ]);
    }
}
