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
        $base = User::query()
            ->when($q, function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%$q%")
                      ->orWhere('email', 'like', "%$q%");
                });
            })
            ->orderBy('id', 'desc');

        $users = $base->clone()->paginate(24)->withQueryString();

        // Stats
        $total = $base->clone()->count();
        $admins = $base->clone()->where('is_admin', true)->count();
        $nonAdmins = $total - $admins;
        $createdMonth = $base->clone()->whereBetween('created_at',[now()->startOfMonth(), now()->endOfMonth()])->count();
        $updatedMonth = $base->clone()->whereBetween('updated_at',[now()->startOfMonth(), now()->endOfMonth()])->count();
        $withPasswordOld = $base->clone()->where('updated_at','<',now()->subYear())->count();
        $recentlyActive = method_exists(User::class,'scopeWhere') ? 0 : 0; // placeholder if activity tracking added later
        $stats = compact('total','admins','nonAdmins','createdMonth','updatedMonth','withPasswordOld','recentlyActive');

        return view('apps.user-contacts', compact('users', 'q','stats'));
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
