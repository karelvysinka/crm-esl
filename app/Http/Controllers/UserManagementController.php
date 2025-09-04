<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    private function ensureAdmin(): void
    {
        if (!auth()->check() || !auth()->user()->is_admin) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $this->ensureAdmin();
        $q = trim((string) $request->input('q', ''));
        $users = User::query()
            ->when($q, function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%$q%")
                      ->orWhere('email', 'like', "%$q%");
                });
            })
            ->orderBy('id', 'desc')
            ->paginate(24)
            ->withQueryString();

        return view('apps.user-contacts', compact('users', 'q'));
    }

    public function edit(User $user)
    {
        $this->ensureAdmin();
        return view('apps.user-profile', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->ensureAdmin();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'is_admin' => 'nullable|boolean',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (!empty($validated['password'])) {
            // 'hashed' cast on model will hash it
            $user->password = $validated['password'];
        }
        $user->is_admin = (bool) ($validated['is_admin'] ?? false);
        $user->save();

        return redirect()->route('apps.users.edit', $user)->with('success', 'Uživatel byl aktualizován.');
    }
}
