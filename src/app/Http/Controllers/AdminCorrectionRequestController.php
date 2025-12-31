<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CorrectionRequest;
use Carbon\Carbon;

class AdminCorrectionRequestController extends Controller
{
    public function show(int $correctionRequestId) {
        $correctionRequest = CorrectionRequest::with([
            'attendance.user',
            'attendance.breaks',
        ])->findOrFail($correctionRequestId);

        $attendance = $correctionRequest->attendance;

        $date = $attendance->work_date instanceof Carbon ? $attendance->work_date : Carbon::parse($attendance->work_date);

        $display = [];

        $display['clock_in'] = !empty($correctionRequest->requested_clock_in) ? Carbon::parse($correctionRequest->requested_clock_in)->format('H:i') : (!empty($attendance->clock_in) ? $attendance->clock_in->format('H:i') : '');

        $display['clock_out'] = !empty($correctionRequest->requested_clock_out) ? Carbon::parse($correctionRequest->requested_clock_out)->format('H:i') : (!empty($attendance->clock_out) ? $attendance->clock_out->format('H:i') : '');

        if (is_array($correctionRequest->requested_breaks) && count($correctionRequest->requested_breaks) > 0) {
            $display['breaks'] = collect($correctionRequest->requested_breaks)->map(function ($row) {
                return [
                    'start' => !empty($row['start']) ? Carbon::parse($row['start'])->format('H:i') : '',
                    'end' => !empty($row['end']) ? Carbon::parse($row['end'])->format('H:i') : '',
                ];
            })->toArray();
        } else {
            $display['breaks'] = $attendance->breaks->map(function ($breakTime) {
                return [
                    'start' => !empty($breakTime->break_start) ? $breakTime->break_start->format('H:i') : '',
                    'end' => !empty($breakTime->break_end) ? $breakTime->break_end->format('H:i') : '',
                ];
            })->toArray();
        }

        $display['comment'] = !empty($correctionRequest->comment) ? $correctionRequest->comment : ($attendance->note ?? '');

        return view('attendance.detail', [
            'mode' => 'admin',
            'attendance' => $attendance,
            'date' => $date,
            'display' => $display,
            'isPending' => $correctionRequest->status === CorrectionRequest::STATUS_PENDING,
            'isApprovalScreen' => true,
            'isApproved' => $correctionRequest->status === CorrectionRequest::STATUS_APPROVED,
            'requestId' => $correctionRequest->id,
        ]);
    }

    public function approve(Request $request, int $correctionRequestId) {
        $correctionRequest = CorrectionRequest::with('attendance.breaks')->findOrFail($correctionRequestId);

        if ($correctionRequest->status !== CorrectionRequest::STATUS_PENDING) {
            return redirect()
            ->route('admin.request.show', ['id' => $correctionRequest->id])
            ->with('message', 'この申請はすでに処理されています。');
        }

        DB::transaction(function () use ($correctionRequest) {
            $attendance = $correctionRequest->attendance;

            if (!empty($correctionRequest->requested_clock_in)) {
                $attendance->clock_in = Carbon::parse($correctionRequest->requested_clock_in);
            }
            if (!empty($correctionRequest->requested_clock_out)) {
                $attendance->clock_out = Carbon::parse($correctionRequest->requested_clock_out);
            }

            if (is_array($correctionRequest->requested_breaks) && count($correctionRequest->requested_breaks) > 0) {
                $attendance->breaks()->delete();

                $breakNumber = 1;
                foreach ($correctionRequest->requested_breaks as $row) {
                    if (empty($row['start']) && empty($row['end'])) {
                        continue;
                    }
                    $attendance->breaks()->create([
                        'break_no' => $breakNumber,
                        'break_start' => !empty($row['start']) ? Carbon::parse($row['start']) : null,
                        'break_end' => !empty($row['end']) ? Carbon::parse($row['end']) : null,
                    ]);

                    $breakNumber++;
                }
            }

            if (!empty($correctionRequest->comment)) {
                $attendance->note = $correctionRequest->comment;
            }

            $attendance->unsetRelation('breaks');
            $attendance->load('breaks');

            if (!empty($attendance->clock_in) && !empty($attendance->clock_out)) {
                $clockIn = Carbon::parse($attendance->clock_in)->seconds(0);
                $clockOut = Carbon::parse($attendance->clock_out)->seconds(0);

                $totalWorkMinutes = $clockIn->diffInMinutes($clockOut);

                $totalBreakMinutes = 0;
                foreach ($attendance->breaks as $breakTime) {
                    if (!empty($breakTime->break_start) && !empty($breakTime->break_end)) {
                        $start = Carbon::parse($breakTime->break_start)->seconds(0);
                        $end = Carbon::parse($breakTime->break_end)->seconds(0);

                        $totalBreakMinutes += $start->diffInMinutes($end);
                    }
                }

                $attendance->total_break_minutes = $totalBreakMinutes;
                $attendance->total_work_minutes = max($totalWorkMinutes - $totalBreakMinutes, 0);
            }
            $attendance->save();

            $correctionRequest->status = CorrectionRequest::STATUS_APPROVED;
            $correctionRequest->save();
        });

        return redirect()
        ->route('admin.request.show', ['id' => $correctionRequest->id])
        ->with('message', '申請を承認しました。');
    }
}
