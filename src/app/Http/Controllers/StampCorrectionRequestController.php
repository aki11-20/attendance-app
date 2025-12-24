<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreCorrectionRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\CorrectionRequest;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Models\User;

class StampCorrectionRequestController extends Controller
{
    public function index(Request $request) {
        $user = Auth::user();
        $tab = $request->query('tab', 'pending');

        if ($user->role === User::ROLE_ADMIN) {
            $query = CorrectionRequest::with(['attendance', 'user']);
        } else {
            $query = CorrectionRequest::with(['attendance', 'user'])
            ->where('user_id', $user->id);
        }

        if ($tab === 'approved') {
            $query->where('status', CorrectionRequest::STATUS_APPROVED);
        } else {
            $query->where('status', CorrectionRequest::STATUS_PENDING);
        }

        $items = $query->orderByDesc('created_at')->get();

        $view = $user->role === User::ROLE_ADMIN ? 'admin.stamp-request-list' : 'stamp_correction_request.list';

        return view($view, compact('tab', 'items'));
    }

    public function store(StoreCorrectionRequest $request, Attendance $attendance) {
        $user = Auth::user();

        if ($attendance->user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validated();

        $workDate = $attendance->work_date instanceof Carbon ? $attendance->work_date : Carbon::parse($attendance->work_date);

        $requestedClockIn = !empty($data['clock_in']) ? Carbon::parse($workDate->format('Y-m-d') . ' ' . $data['clock_in']) : null;

        $requestedClockOut = !empty($data['clock_out']) ? Carbon::parse($workDate->format('Y-m-d') . ' ' . $data['clock_out']) : null;

        $requestedBreaks =[];
        if (!empty($data['breaks']) && is_array($data['breaks'])) {
            foreach ($data['breaks'] as $row) {
                if (!empty($row['start']) || !empty($row['end'])) {
                    $requestedBreaks[] = [
                        'start' => !empty($row['start']) ? $workDate->format('Y-m-d') . ' ' . $row['start'] : null,
                        'end' => !empty($row['end']) ? $workDate->format('Y-m-d') . ' ' . $row['end'] : null,
                    ];
                }
            }
        }

        CorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => CorrectionRequest::STATUS_PENDING,
            'requested_clock_in' => $requestedClockIn,
            'requested_clock_out' => $requestedClockOut,
            'requested_breaks' => $requestedBreaks,
            'comment' => $data['comment'],
        ]);

        return redirect()
        ->route('stamp_requests.index', ['tab' => 'pending'])
        ->with('message', '修正申請を送信しました。');
    }
}
