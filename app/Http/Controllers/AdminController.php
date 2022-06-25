<?php

namespace App\Http\Controllers;
use Illuminate\Database\Eloquent\Model;
use App\Models\Sql\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController
{
    public function login(Request $request) {
        return view('admin.login');
    }

    public function loginSubmit(Request $request) {
        $username = $request->post('username', '');
        $password = $request->post('password', '');
        $password_hash = Hash::make($password);
        $user = User::query()->where('username', $username)->where($password_hash)->first();
        if ()
    }
}
