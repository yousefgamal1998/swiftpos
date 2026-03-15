<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->with('roles:name')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'created_at',
            ])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'roles' => $user->roles->pluck('name')->values(),
            ]);

        return Inertia::render('Users/Index', [
            'users' => $users,
        ]);
    }
}
