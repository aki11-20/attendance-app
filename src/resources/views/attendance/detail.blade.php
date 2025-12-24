@extends('layouts.app')

@section('title', '勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/common.css') }}">
<link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">
@endsection

@section('header')
@if (($mode ?? 'user') === 'admin')
@include('components.admin')
@else
@include('components.user')
@endif
@endsection

@section('content')
@php
$mode = $mode ?? 'user';
$isPending = $isPending ?? false;

$isApprovalScreen = $isApprovalScreen ?? false;

$adminCanApprove = $adminCanApprove ?? ($mode === 'admin' && $isApprovalScreen && !($isApproved ?? false));

$adminApproved = $adminApproved ?? ($mode === 'admin' && $isApprovalScreen && ($isApproved ?? false));

$editable = $editable ?? (($mode === 'user' && !$isPending) || ($mode === 'admin' && !$isApprovalScreen && !$isPending));

if (!isset($formAction)) {
if ($editable && $mode === 'user') {
$formAction = route('stamp_requests.store', ['attendance' => $attendance->id]);
} elseif ($editable && $mode === 'admin') {
$formAction = route('admin.attendance.update', ['id' => $attendance->id]);
} elseif ($adminCanApprove || $adminApproved) {
$formAction = route('admin.request.approve', ['id' => $requestId]);
} else {
$formAction = '';
}
}
$display = $display ?? ['clock_in' => '', 'clock_out' => '', 'breaks' => [], 'comment' => ''];
@endphp

<div class="attendance-detail-wrap">
    <div class="attendance-detail-card">
        <h1 class="attendance-detail-title">勤怠詳細</h1>

        @if (session('message') && !$adminApproved)
        <div class="flash-message">
            {{ session('message') }}
        </div>
        @endif

        @if ($editable)
        <form class=" attendance-detail-form" action="{{ $formAction }}" method="POST">
            @csrf
            @elseif ($adminCanApprove || $adminApproved)
            <form class="attendance-detail-form" action="{{ $formAction }}" method="POST">
                @csrf
                @else
                <div class="attendance-detail-form">
                    @endif
                    <table class="attendance-detail-table">
                        <colgroup>
                            <col class="col-label">
                            <col class="col-left">
                            <col class="col-separator">
                            <col class="col-right">
                        </colgroup>

                        <tr class="row-name">
                            <th>名前</th>
                            <td colspan="3">{{ $attendance->user->name ?? '' }}</td>
                        </tr>

                        <tr>
                            <th>日付</th>
                            <td colspan="3" class="time-range-cell">
                                <div class="time-range">
                                    <span class="time-text">{{ $date->format('Y') }}年</span>
                                    <span class="time-tilde"></span>
                                    <span class="time-text">{{ $date->format('n') }}月{{ $date->format('j') }}日</span>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th>出勤・退勤</th>
                            <td colspan="3" class="time-range-cell">
                                @if ($editable)
                                <div class="time-range">
                                    <input class="detail-input" type="time" name="clock_in" value="{{ old('clock_in', $display['clock_in']) }}">
                                    <span class="time-tilde">〜</span>
                                    <input class="detail-input" type="time" name="clock_out" value="{{ old('clock_out', $display['clock_out']) }}">
                                </div>

                                @error('clock_in')
                                <div class="error">
                                    {{ $message }}
                                </div>
                                @enderror

                                @error('clock_out')
                                <div class="error">
                                    {{ $message }}
                                </div>
                                @enderror
                                @else
                                <div class="time-range">
                                    <span class="time-text">{{ $display['clock_in'] }}</span>
                                    <span class="time-tilde">〜</span>
                                    <span class="time-text">{{ $display['clock_out'] }}</span>
                                </div>
                                @endif
                            </td>
                        </tr>

                        @php
                        $breakRows = $display['breaks'] ?? [];
                        $minRows = 2;
                        $extraInputRows = 0;
                        if ($editable) {
                            $completed = collect($breakRows)->filter(function ($breakRow) {
                                return !empty($breakRow['start']) && !empty($breakRow['end']);
                            })->count();

                            $extraInputRows = max($completed - 1, 0);
                        }
                        $rowCount = max(count($breakRows), $minRows) + $extraInputRows;
                        @endphp

                        @for ($breakIndex = 0; $breakIndex < $rowCount; $breakIndex++)
                            @php
                            $currentBreak=$breakRows[$breakIndex] ?? ['start'=> '', 'end' => ''];
                            $breakLabel = $breakIndex === 0 ? '休憩' : '休憩' . ($breakIndex + 1);
                            @endphp

                            <tr>
                                <th>{{ $breakLabel }}</th>
                                <td colspan="3" class="time-range-cell">
                                    @if ($editable)
                                    <div class="time-range">
                                        <input class="detail-input" type="time" name="breaks[{{ $breakIndex }}][start]" value="{{ old("breaks.$breakIndex.start", $currentBreak['start']) }}">
                                        <span class="time-tilde">〜</span>
                                        <input class="detail-input" type="time" name="breaks[{{ $breakIndex }}][end]" value="{{ old("breaks.$breakIndex.end", $currentBreak['end']) }}">
                                    </div>
                                    @error("breaks.$breakIndex.start")
                                    <div class="error">
                                        {{ $message }}
                                    </div>
                                    @enderror

                                    @error("breaks.$breakIndex.end")
                                    <div class="error">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                    @else
                                    <div class="time-range">
                                        <span class="time-text">{{ $currentBreak['start'] ?: '-' }}</span>
                                        <span class="time-tilde">〜</span>
                                        <span class="time-text">{{ $currentBreak['end'] ?: '-' }}</span>
                                    </div>
                                    @endif
                                </td>
                            </tr>
                            @endfor

                            <tr class="row-note">
                                <th>備考</th>
                                <td colspan="3">
                                    @if ($editable)
                                    <textarea class="detail-textarea" name="comment" rows="3">{{ old('comment', $display['comment']) }}</textarea>
                                    @error('comment')
                                    <div class="error">
                                        {{ $message }}
                                    </div>
                                    @enderror

                                    @else
                                    {{ $display['comment'] }}
                                    @endif
                                </td>
                            </tr>
                    </table>

                    <div class="detail-submit">
                        @if ($editable)
                        <button class="detail-submit-btn" type="submit">修正</button>

                        @elseif ($mode === 'user' && $isPending)
                        <p class="detail-notice">※承認待ちのため修正はできません。</p>

                        @elseif ($mode === 'admin' && $adminCanApprove)
                        <button class="detail-submit-btn" type="submit">
                            承認
                        </button>

                        @elseif ($mode === 'admin' && $adminApproved)
                        <button class="detail-submit-btn" type="button" disabled>
                            承認済み
                        </button>
                        @endif
                    </div>

                    @if ($editable || $adminCanApprove || $adminApproved)
            </form>
            @else
    </div>
    @endif
</div>
</div>
@endsection