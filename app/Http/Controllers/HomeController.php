<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $companies = Company::query()
            ->withCount(['jobs as open_jobs_count' => fn ($query) => $query->open()])
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'trade_name', 'slug']);

        return Inertia::render('Welcome', [
            'companies' => $companies->map(fn (Company $company): array => [
                'name' => $company->displayName(),
                'slug' => $company->slug,
                'open_jobs_count' => $company->open_jobs_count,
            ]),
        ]);
    }
}
