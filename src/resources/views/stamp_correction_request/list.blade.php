@extends('layouts.app')

@section('title', '申請一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/common.css') }}">
<link rel="stylesheet" href="{{ asset('css/stamp-request-list.css') }}">
@endsection

@section('header')
@include('components.user')
@endsection

@section('content')
<div class="request-list-wrap">
    <div class="request-list-card">
        <h1 class="request-list-title">申請一覧</h1>

        <div class="request-tabs">
            <a class="request-tab {{ $tab === 'pending' ? 'is-active' : '' }}" href="{{ route('stamp_requests.index', ['tab' => 'pending']) }}">
                承認待ち
            </a>
            <a class="request-tab {{ $tab === 'approved' ? 'is-active' : '' }}" href="{{ route('stamp_requests.index', ['tab' => 'approved']) }}">
                承認済み
            </a>
        </div>

        <table class="request-list-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                <tr>
                    <td>
                        @if ($item->status === \App\Models\CorrectionRequest::STATUS_PENDING)
                        承認待ち
                        @elseif ($item->status === \App\Models\CorrectionRequest::STATUS_APPROVED)
                        承認済み
                        @else
                        却下
                        @endif
                    </td>
                    <td>{{ $item->user->name }}</td>
                    <td>{{ optional($item->attendance->work_date)->format('Y/m/d') }}</td>
                    <td>{{ $item->comment }}</td>
                    <td>{{ $item->created_at->format('Y/m/d') }}</td>
                    <td>
                        <a class="request-detail-link" href="{{ route('attendance.detail', ['id' => $item->attendance_id]) }}">詳細</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td class="request-empty" colspan="6">
                        申請はありません。
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
