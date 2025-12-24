@extends('layouts.app')

@section('title', '勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/common.css') }}">
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection

@section('header')
@include('components.user')
@endsection

@section('content')
<div class="attendance-list-wrap">
    <div class="attendance-list-card">
        <h1 class="attendance-list-title">勤怠一覧</h1>
        <div class="attendance-list-month-nav">
            <a class="month-nav-btn" href="{{ route('attendance.monthly', ['month' => $prevMonthParam]) }}">
                <span class="arrow">←</span> 前月
            </a>
            <div class="month-display">
                <span class="month-display-icon">🗓️</span>
                <span class="month-display-text">{{ $currentMonthLabel }}</span>
            </div>
            <a class="month-nav-btn" href="{{ route('attendance.monthly', ['month' => $nextMonthParam]) }}">
                翌月 <span class="arrow">→</span>
            </a>
        </div>

        <table class="attendance-list-table">
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
                        <a class="detail-link" href="{{ route('attendance.detail', ['id' => $day['id']]) }}">
                            詳細
                        </a>
                        @else
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
