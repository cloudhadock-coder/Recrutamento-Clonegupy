<?php

namespace App\Jobs;

use App\Models\Application;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Extrai o texto do currículo em PDF com `pdftotext` (poppler-utils / xpdf) para busca e leitura rápida.
 */
class ExtractResumeText implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * A candidatura pode ter sido excluída pelo candidato (LGPD) antes do job rodar.
     */
    public bool $deleteWhenMissingModels = true;

    private const MAX_CHARACTERS = 100_000;

    public function __construct(public Application $application) {}

    public function handle(): void
    {
        if ($this->application->resume_path === null) {
            return;
        }

        $temporaryFile = tempnam(sys_get_temp_dir(), 'resume');

        try {
            // Copia para um arquivo local: o disco pode ser S3/R2 em produção.
            file_put_contents($temporaryFile, Storage::disk(config('filesystems.resumes'))->get($this->application->resume_path));

            $result = Process::timeout(60)->run([config('services.pdftotext.path'), '-enc', 'UTF-8', '-layout', $temporaryFile, '-']);

            if ($result->failed()) {
                throw new RuntimeException("pdftotext falhou para a candidatura {$this->application->id}: {$result->errorOutput()}");
            }

            $text = trim(str_replace("\0", '', mb_convert_encoding($result->output(), 'UTF-8', 'UTF-8')));

            $this->application->forceFill([
                'resume_text' => mb_substr($text, 0, self::MAX_CHARACTERS),
                'resume_text_extracted_at' => now(),
            ])->save();
        } finally {
            @unlink($temporaryFile);
        }
    }
}
