@extends('layouts.app')

@section('title', $user->name . 'さんの勤怠')

@section('css')
<link rel="stylesheet" href="{{ asset('css/common.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-attendance-staff-monthly.css') }}">
@endsection

@section('header')
@include('components.admin')
@endsection

@section('content')
<div class="admin-staff-month-wrap">
    <div class="admin-staff-month-card">
        <h1 class="admin-staff-month-title">
            {{ $user->name }}さんの勤怠
        </h1>

        <div class="admin-staff-month-nav">
            <a class="month-nav-btn" href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => $prevMonthParam]) }}">
                ← 前月
            </a>

            <div class="month-display">
                <span class="month-display-icon">🗓️</span>
                <span class="month-display-text">{{ $currentMonthLabel }}</span>
            </div>

            <a class="month-nav-btn" href="{{ route('admin.attendance.staff',['id' => $user->id, 'month' => $nextMonthParam]) }}">
                翌月 →
            </a>
        </div>

        <table class="admin-staff-month-table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($days as $day)
                <tr>
                    <td>{{ $day['date']->translatedFormat('m/d(D)') }}</td>
                    <td>{{ $day['in'] }}</td>
                    <td>{{ $day['out'] }}</td>
                    <td>{{ $day['break'] }}</td>
                    <td>{{ $day['total'] }}</td>
                    <td>
                        @if ($day['id'])
                        <a class="detail-link" href="{{ route('admin.attendance.detail', ['id' => $day['id']]) }}">
                            詳細
                        </a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="admin-staff-month-footer">
            <a class="csv-btn" href="{{ route('admin.attendance.staff.export', ['id' => $user->id, 'month' => request('month')]) }}">CSV出力</a>
        </div>
    </div>
</div>
@endsection
