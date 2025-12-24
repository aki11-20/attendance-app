@extends('layouts.app')

@section('title', '勤怠一覧(管理者)')

@section('css')
<link rel="stylesheet" href="{{ asset('css/common.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-attendance-list.css') }}">
@endsection

@section('header')
@include('components.admin')
@endsection

@section('content')
<div class="admin-attendance-list-wrap">
    <div class="admin-attendance-list-card">
        <h1 class="admin-attendance-list-title">{{ $target->format('Y年n月j日') }}の勤怠</h1>

        <div class="admin-attendance-date-nav">
            <a class="admin-nav-btn" href="{{ route('admin.attendance.list', ['date' => $target->copy()->subDay()->format('Y-m-d')]) }}">
                ← 前日
            </a>
            <div class="admin-date-display">
                <span class="admin-display-icon">🗓️</span>
                <span class="admin-display-text">
                    {{ $target->format('Y/m/d') }}
                </span>
            </div>

            <a class="admin-nav-btn" href="{{ route('admin.attendance.list', ['date' => $target->copy()->addDay()->format('Y-m-d')]) }}">
                翌日 →
            </a>
        </div>

        <table class="admin-attendance-list-table">
            <colgroup>
                <col class="col-name">
                <col class="col-in">
                <col class="col-out">
                <col class="col-break">
                <col class="col-total">
                <col class="col-detail">
            </colgroup>
            <thead>
                <tr>
                    <th>名前</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($attendances as $attendance)
                <tr>
                    <td class="cell-name">{{ $attendance->user->name }}</td>
                    <td>{{ $attendance->clock_in ? $attendance->clock_in->format('H:i') : '-' }}</td>
                    <td>{{ $attendance->clock_out ? $attendance->clock_out->format('H:i') : '-' }}</td>
                    <td>
                        {{ $attendance->total_break_minutes !== null ? sprintf('%d:%02d', intdiv($attendance->total_break_minutes, 60), $attendance->total_break_minutes % 60) : '-' }}
                    </td>
                    <td>
                        {{ $attendance->total_work_minutes !== null ? sprintf('%d:%02d', intdiv($attendance->total_work_minutes, 60), $attendance->total_work_minutes % 60) : '-' }}
                    </td>
                    <td class="cell-detail">
                        <a class="admin-detail-link" href="{{ route('admin.attendance.detail', ['id' => $attendance->id]) }}">
                            詳細
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td class="admin-attendance-empty" colspan="6">対象日の勤怠はありません。
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection