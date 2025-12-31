<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;

class AdminStaffController extends Controller
{
    public function index() {
        $users = User::where('role', 0)
        ->orderBy('name')
        ->get();

        return view('admin.staff-list', compact('users'));
    }
}
