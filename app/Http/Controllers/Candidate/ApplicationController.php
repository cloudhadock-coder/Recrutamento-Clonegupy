<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    /**
     * Candidaturas do candidato em todas as empresas, com paginação por cursor.
     */
    public function index(Request $request): Response
    {
        $applications = $request->user('candidate')
            ->applications()
            ->with(['job:id,company_id,title,slug,status', 'job.company:id,name,trade_name,slug', 'stage:id,name'])
            ->orderByDesc('id')
            ->cursorPaginate(10, ['id', 'job_id', 'job_stage_id', 'candidate_id', 'status', 'created_at', 'consent_at']);

        return Inertia::render('Candidate/Applications', [
            'applications' => $applications,
        ]);
    }
}
