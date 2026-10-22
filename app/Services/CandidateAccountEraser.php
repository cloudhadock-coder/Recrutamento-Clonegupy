<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Candidate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Direitos do titular (LGPD, art. 18): exclusão ou anonimização da conta do candidato.
 */
class CandidateAccountEraser
{
    /**
     * Apaga a conta, as candidaturas (e o histórico delas, em cascata) e os currículos.
     */
    public function delete(Candidate $candidate): void
    {
        $resumePaths = $this->resumePaths($candidate);

        DB::transaction(fn () => $candidate->delete());

        $this->deleteResumes($resumePaths);
    }

    /**
     * Remove todos os dados pessoais e mantém as candidaturas anônimas, preservando as
     * estatísticas das empresas (etapas, datas e histórico de movimentações).
     */
    public function anonymize(Candidate $candidate): void
    {
        $resumePaths = $this->resumePaths($candidate);

        DB::transaction(function () use ($candidate): void {
            $candidate->forceFill([
                'name' => 'Candidato anonimizado',
                'email' => "anonimizado-{$candidate->id}@anonimizado.invalid",
                'phone' => null,
                'linkedin_url' => null,
                'cep' => null,
                'city_id' => null,
                'state' => null,
                'password' => Str::random(64),
                'remember_token' => null,
                'email_verified_at' => null,
                'anonymized_at' => now(),
            ])->save();

            Application::query()->where('candidate_id', $candidate->id)->update([
                'answers' => null,
                'resume_path' => null,
                'resume_original_name' => null,
                'resume_text' => null,
                'consent_ip' => null,
            ]);
        });

        $this->deleteResumes($resumePaths);
    }

    /**
     * @return list<string>
     */
    private function resumePaths(Candidate $candidate): array
    {
        return Application::query()
            ->where('candidate_id', $candidate->id)
            ->whereNotNull('resume_path')
            ->pluck('resume_path')
            ->all();
    }

    /**
     * @param  list<string>  $paths
     */
    private function deleteResumes(array $paths): void
    {
        if ($paths !== []) {
            Storage::disk(config('filesystems.resumes'))->delete($paths);
        }
    }
}
