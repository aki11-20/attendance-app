@extends('layouts.app')

@section('title','ログイン')

@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
<link rel="stylesheet" href="{{ asset('css/common.css') }}">
@endsection

@section('header')
@include('components.logo-only')
@endsection

@section('content')
<div class="login-wrap">
    <div class="login-card">
        <h1 class="login-title">ログイン</h1>
        <form class="login-form" action="/login" method="POST">
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
                <button class="login-btn">ログインする</button>
                <a href="/register" class="register-link">会員登録はこちら</a>
            </div>
        </form>
    </div>
</div>
@endsection
