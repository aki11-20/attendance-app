<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use App\Models\User;

class LogoutResponse implements LogoutResponseContract {
    public function toResponse($request) {
        $user = $request->user();

        if ($user && $user->role === User::ROLE_ADMIN) {
            return redirect()->route('admin.login');
        }

        return redirect()->route('login');
    }
}