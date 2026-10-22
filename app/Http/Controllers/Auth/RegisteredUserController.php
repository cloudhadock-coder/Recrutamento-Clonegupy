<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompanyRegistrationRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Cadastro de empresa: cria a empresa e o primeiro usuário (administrador).
 */
class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(CompanyRegistrationRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $company = Company::create($request->safe()->except(['admin_name', 'admin_email', 'password', 'password_confirmation']));

            return $company->users()->create([
                'name' => $request->validated('admin_name'),
                'email' => $request->validated('admin_email'),
                'password' => $request->validated('password'),
                'role' => UserRole::Admin,
            ]);
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
