<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        // Pokud je uživatel již přihlášen, přesměruj na hlavní CRM dashboard
        if (Auth::check()) {
            return redirect('/crm');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        if (Auth::check()) { // dvojité odeslání formuláře nebo otevřené více okno
            return redirect('/crm');
        }
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended('/crm');
        }

        return back()->withErrors(['email' => 'Neplatné přihlašovací údaje'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    public function createAdmin(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);
        $user = User::firstOrCreate(
            ['email' => $request->email],
            ['name' => $request->name, 'password' => Hash::make($request->password), 'is_admin' => true]
        );
        if (!$user->is_admin) { $user->is_admin = true; $user->save(); }
        return back()->with('success', 'Admin uživatel vytvořen/aktualizován.');
    }
}
