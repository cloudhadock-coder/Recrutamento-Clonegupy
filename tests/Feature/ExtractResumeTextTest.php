<?php

use App\Jobs\ExtractResumeText;
use App\Models\Application;
use Database\Seeders\ResumePdf;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

test('extracts the resume text with pdftotext', function () {
    Storage::fake('local');
    Storage::disk('local')->put('resumes/1/cv.pdf', ResumePdf::make(['Maria Souza', 'Experiência com Laravel e PostgreSQL']));

    $application = Application::factory()->create(['resume_path' => 'resumes/1/cv.pdf']);

    ExtractResumeText::dispatchSync($application);

    expect($application->fresh())
        ->resume_text->toContain('Maria Souza')
        ->resume_text->toContain('Experiência com Laravel')
        ->resume_text_extracted_at->not->toBeNull();
})->skip(function (): bool {
    // O xpdf sai com código 99 em `-v`; basta o binário responder com a versão.
    $version = Process::run([config('services.pdftotext.path'), '-v']);

    return ! str_contains($version->output().$version->errorOutput(), 'pdftotext version');
}, 'pdftotext não está instalado.');

test('fails loudly when pdftotext cannot read the file, so the queue retries', function () {
    Storage::fake('local');
    Storage::disk('local')->put('resumes/1/cv.pdf', 'não é um PDF');
    Process::fake(['*' => Process::result(errorOutput: 'Syntax Error', exitCode: 1)]);

    $application = Application::factory()->create(['resume_path' => 'resumes/1/cv.pdf']);

    ExtractResumeText::dispatchSync($application);
})->throws(RuntimeException::class);
