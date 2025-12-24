@extends('layouts.app')

@section('title', '管理者ログイン')

@section('css')
<link rel="stylesheet" href="{{ asset('css/common.css') }}">
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('header')
@include('components.logo-only')
@endsection

@section('content')
<div class="login-wrap">
    <div class="login-card">
        <h1 class="login-title">管理者ログイン</h1>
        <form class="login-form" action="{{ route('admin.login.post') }}" method="POST">
            @csrf

            <div class="form-group">
                <label class="form-label" for="email">メールアドレス</label>
                <input class="form-input" type="email" id="email" name="email" value="{{ old('email') }}">
                <div class="error">
                    @error('email')
                    {{ $message }}
                    @enderror
                </div>
                <label class="form-label" for="password">パスワード</label>
                <input class="form-input" type="password" id="password" name="password">
                <div class="error">
                    @error('password')
                    {{ $message }}
                    @enderror
                </div>
                <button class="login-btn">管理者ログインする</button>
            </div>
        </form>
    </div>
</div>
@endsection
