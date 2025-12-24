@extends('layouts.app')

@section('title','会員登録')

@section('css')
<link rel="stylesheet" href="{{ asset('css/common.css') }}">
<link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endsection

@section('header')
@include('components.logo-only')
@endsection

@section('content')
<div class="register-wrap">
    <div class="register-card">
        <h1 class="register-title">会員登録</h1>
        <form class="register-form" action="/register" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label" for="name">名前</label>
                <input class="form-input" type="text" id="name" name="name" value="{{ old('name') }}">
                <div class="error">
                    @error('name')
                    {{ $message }}
                    @enderror
                </div>
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
                <label class="form-label" for="password_confirm">パスワード確認</label>
                <input class="form-input" type="password" id="password_confirmation" name="password_confirmation">
                <button class="register-btn">登録する</button>
                <a href="/login" class="login-link">ログインはこちら</a>
            </div>
        </form>
    </div>
</div>
@endsection
