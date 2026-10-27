<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::attempt($credentials + ['status' => 'active', fn (Builder $query): Builder => $query->where(fn (Builder $query): Builder => $query->where('is_admin', true)->orWhere('role', 'pilot'))])) {
            throw ValidationException::withMessages(['email' => 'The sign-in details could not be verified.']);
        }
        $request->session()->regenerate();

        return redirect()->intended(Auth::user()->isOperationsAdmin() ? route('admin.visits.index') : route('ops.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
