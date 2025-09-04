<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        abort_unless(auth()->check(), 403);
        $user = $request->user();
        return view('apps.my-profile', compact('user'));
    }

    public function update(Request $request)
    {
        abort_unless(auth()->check(), 403);
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (!empty($validated['password'])) {
            $user->password = $validated['password']; // hashed by model cast
        }
        $user->save();

        return redirect()->route('apps.me.show')->with('success', 'Profil byl aktualizován.');
    }
}
