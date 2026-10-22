<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationStageChanged extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * A candidatura deve vir com candidate, job.company e stage carregados.
     */
    public function __construct(public Application $application)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Sua candidatura para {$this->application->job->title} avançou",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.applications.stage-changed',
            with: [
                'candidateName' => $this->application->candidate->name,
                'jobTitle' => $this->application->job->title,
                'companyName' => $this->application->job->company->displayName(),
                'stageName' => $this->application->stage->name,
                'applicationsUrl' => route('candidate.applications.index'),
            ],
        );
    }
}
