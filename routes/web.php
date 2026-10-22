<?php

use App\Http\Controllers\Candidate;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\Panel;
use App\Http\Controllers\Portal;
use App\Http\Controllers\ProfileController;
use App\Models\Company;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

/*
| Consultas para formulários (BrasilAPI, ViaCEP e cidades do IBGE).
*/
Route::prefix('utils')->name('lookup.')->middleware('throttle:30,1')->group(function () {
    Route::get('cnpj/{cnpj}', [LookupController::class, 'cnpj'])->name('cnpj');
    Route::get('cep/{cep}', [LookupController::class, 'cep'])->name('cep');
    Route::get('cidades', [LookupController::class, 'cities'])->name('cities');
});

/*
| Painel do recrutador (guard "web"). O middleware "company" define o tenant.
*/
Route::middleware(['auth', 'company', 'no-ssr'])->group(function () {
    Route::get('/painel', Panel\DashboardController::class)->name('dashboard');

    Route::prefix('painel')->name('panel.')->group(function () {
        Route::resource('vagas', Panel\JobController::class)
            ->parameters(['vagas' => 'job'])
            ->names('jobs');
        Route::post('vagas/{job}/publicar', [Panel\JobController::class, 'publish'])->name('jobs.publish');
        Route::post('vagas/{job}/pausar', [Panel\JobController::class, 'pause'])->name('jobs.pause');
        Route::get('vagas/{job}/etapas/{stage}/candidaturas', [Panel\JobController::class, 'column'])->name('jobs.column')->scopeBindings();

        Route::get('vagas/{job}/etapas', [Panel\JobStageController::class, 'index'])->name('jobs.stages.index');
        Route::post('vagas/{job}/etapas', [Panel\JobStageController::class, 'store'])->name('jobs.stages.store');
        Route::put('vagas/{job}/etapas/ordem', [Panel\JobStageController::class, 'reorder'])->name('jobs.stages.reorder');
        Route::patch('vagas/{job}/etapas/{stage}', [Panel\JobStageController::class, 'update'])->name('jobs.stages.update')->scopeBindings();
        Route::delete('vagas/{job}/etapas/{stage}', [Panel\JobStageController::class, 'destroy'])->name('jobs.stages.destroy')->scopeBindings();

        Route::get('candidaturas', [Panel\ApplicationController::class, 'index'])->name('applications.index');
        Route::get('candidaturas/{application}', [Panel\ApplicationController::class, 'show'])->name('applications.show');
        Route::get('candidaturas/{application}/curriculo', [Panel\ApplicationController::class, 'resume'])->name('applications.resume');
        Route::patch('candidaturas/{application}/etapa', [Panel\ApplicationController::class, 'move'])->name('applications.move');
        Route::post('candidaturas/{application}/reprovar', [Panel\ApplicationController::class, 'reject'])->name('applications.reject');
        Route::post('candidaturas/{application}/reativar', [Panel\ApplicationController::class, 'reactivate'])->name('applications.reactivate');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

/*
| Área do candidato (guard "candidate").
*/
Route::prefix('candidato')->name('candidate.')->group(function () {
    Route::middleware('guest:candidate')->group(function () {
        Route::get('entrar', [Candidate\AuthController::class, 'login'])->name('login');
        Route::post('entrar', [Candidate\AuthController::class, 'authenticate'])->name('authenticate');
        Route::get('cadastro', [Candidate\AuthController::class, 'register'])->name('register');
        Route::post('cadastro', [Candidate\AuthController::class, 'store'])->name('store');
    });

    Route::middleware('auth:candidate')->group(function () {
        Route::post('sair', [Candidate\AuthController::class, 'logout'])->name('logout');
        Route::get('candidaturas', [Candidate\ApplicationController::class, 'index'])->name('applications.index');
        Route::get('conta', [Candidate\AccountController::class, 'edit'])->name('account.edit');
        Route::patch('conta', [Candidate\AccountController::class, 'update'])->name('account.update');
        Route::delete('conta', [Candidate\AccountController::class, 'destroy'])->name('account.destroy');
    });
});

/*
| Portal de carreiras público: /{slug-da-empresa}/vagas. Registrado por último para não
| capturar as rotas acima; os slugs reservados ficam em Company::RESERVED_SLUGS.
*/
Route::prefix('{company:slug}')->name('portal.')->scopeBindings()->group(function () {
    Route::get('/', fn (Company $company) => to_route('portal.jobs.index', $company))->name('home');
    Route::get('vagas', [Portal\JobBoardController::class, 'index'])->name('jobs.index');
    Route::get('vagas/{job:slug}', [Portal\JobBoardController::class, 'show'])->name('jobs.show');

    Route::middleware('auth:candidate')->group(function () {
        Route::get('vagas/{job:slug}/candidatar', [Portal\ApplyController::class, 'create'])->name('apply.create');
        Route::post('vagas/{job:slug}/candidatar', [Portal\ApplyController::class, 'store'])->name('apply.store');
    });
});
