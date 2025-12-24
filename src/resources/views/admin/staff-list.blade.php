@extends('layouts.app')

@section('title', 'スタッフ一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/common.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-staff-list.css') }}">
@endsection

@section('header')
@include('components.admin')
@endsection

@section('content')
<div class="admin-staff-list-wrap">
    <div class="admin-staff-list-card">
        <h1 class="admin-staff-list-title">スタッフ一覧</h1>

        <table class="admin-staff-list-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>メールアドレス</th>
                    <th>月次勤怠</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <a class="admin-staff-detail-link" href="{{ route('admin.attendance.staff', ['id' => $user->id]) }}">
                            詳細
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td class="admin-staff-empty" colspan="3">
                        スタッフが登録されていません。
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
