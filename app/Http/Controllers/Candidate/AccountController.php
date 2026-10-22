<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Services\CandidateAccountEraser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dados da conta do candidato e direitos do titular (exclusão/anonimização).
 */
class AccountController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Candidate/Account', [
            'candidate' => $request->user('candidate')->load('city'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var Candidate $candidate */
        $candidate = $request->user('candidate');

        $request->merge([
            'phone' => preg_replace('/\D/', '', (string) $request->input('phone')) ?: null,
            'cep' => preg_replace('/\D/', '', (string) $request->input('cep')) ?: null,
        ]);

        $candidate->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(Candidate::class)->ignore($candidate)],
            'phone' => ['nullable', 'digits_between:10,11'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'cep' => ['nullable', 'digits:8'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'state' => ['nullable', 'string', 'size:2'],
        ]));

        return back()->with('success', 'Dados atualizados.');
    }

    public function destroy(Request $request, CandidateAccountEraser $eraser): RedirectResponse
    {
        $validated = $request->validate([
            'mode' => ['required', Rule::in(['delete', 'anonymize'])],
            'password' => ['required', 'current_password:candidate'],
        ]);

        /** @var Candidate $candidate */
        $candidate = $request->user('candidate');

        Auth::guard('candidate')->logout();

        $validated['mode'] === 'delete' ? $eraser->delete($candidate) : $eraser->anonymize($candidate);

        $request->session()->regenerateToken();

        return redirect('/')->with('success', $validated['mode'] === 'delete'
            ? 'Sua conta e seus dados foram excluídos.'
            : 'Sua conta foi anonimizada e seus dados pessoais foram removidos.');
    }
}
