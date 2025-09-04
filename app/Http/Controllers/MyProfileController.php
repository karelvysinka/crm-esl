<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MyProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        return view('apps.user-profile', compact('user'));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (!empty($validated['password'])) {
            $user->password = $validated['password']; // hashed via model's mutator/cast
        }
        // Never allow changing is_admin from self-profile
        $user->save();

        return back()->with('success', 'Profil byl úspěšně aktualizován.');
    }
}
