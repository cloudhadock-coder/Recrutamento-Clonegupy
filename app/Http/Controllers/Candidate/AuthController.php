<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Candidate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Login, cadastro e logout de candidatos (guard "candidate").
 */
class AuthController extends Controller
{
    public function login(): Response
    {
        return Inertia::render('Candidate/Login');
    }

    public function authenticate(LoginRequest $request): RedirectResponse
    {
        $request->authenticate('candidate');

        $request->session()->regenerate();

        return redirect()->intended(route('candidate.applications.index', absolute: false));
    }

    public function register(): Response
    {
        return Inertia::render('Candidate/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:candidates,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $candidate = Candidate::create([
            ...$validated,
            'phone' => preg_replace('/\D/', '', (string) ($validated['phone'] ?? '')) ?: null,
        ]);

        Auth::guard('candidate')->login($candidate);

        $request->session()->regenerate();

        return redirect()->intended(route('candidate.applications.index', absolute: false));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('candidate')->logout();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
