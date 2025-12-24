@extends('layouts.app')

@section('title', '勤怠登録')

@section('css')
<link rel="stylesheet" href="{{ asset('css/common.css') }}">
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('header')
@include('components.user')
@endsection

@section('content')
<div class="attendance-wrap">
    <div class="attendance-status">{{ $status }}</div>
    <p class="attendance-date">{{ $date }}</p>
    <p class="attendance-time">{{ $time }}</p>

    @if ($status === '勤務外')
    <form action="{{ route('attendance.clockIn') }}" method="POST">
        @csrf
        <button class="attendance-start-btn">出勤</button>
    </form>

    @elseif ($status === '出勤中')
    <div class="attendance-button">
        <form action="{{ route('attendance.clockOut') }}" method="POST">
            @csrf
            <button class="attendance-end-btn">退勤</button>
        </form>
        <form action="{{ route('attendance.breakStart') }}" method="POST">
            @csrf
            <button class="attendance-sub-btn">休憩入</button>
        </form>
    </div>

    @elseif ($status === '休憩中')
    <form action="{{ route('attendance.breakEnd') }}" method="POST">
        @csrf
        <button class="attendance-sub-btn">休憩戻</button>
    </form>
    @elseif ($status === '退勤済')
    <p class="attendance-message">お疲れ様でした。</p>
    @endif
</div>
@endsection