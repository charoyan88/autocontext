<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withCount('projects')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }
}
