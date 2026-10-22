<?php

namespace App\Http\Controllers\Panel;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Job;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'openJobs' => Job::query()->open()->count(),
                'activeApplications' => Application::query()->where('status', ApplicationStatus::Active)->count(),
                'applicationsLastWeek' => Application::query()->where('created_at', '>=', now()->subWeek())->count(),
            ],
            'recentApplications' => Application::query()
                ->with(['candidate:id,name', 'job:id,title', 'stage:id,name'])
                ->latest('id')
                ->limit(8)
                ->get(['id', 'job_id', 'candidate_id', 'job_stage_id', 'status', 'created_at']),
        ]);
    }
}
