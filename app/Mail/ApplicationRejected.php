<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationRejected extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * A candidatura deve vir com candidate e job.company carregados.
     */
    public function __construct(public Application $application)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Retorno sobre a vaga {$this->application->job->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.applications.rejected',
            with: [
                'candidateName' => $this->application->candidate->name,
                'jobTitle' => $this->application->job->title,
                'companyName' => $this->application->job->company->displayName(),
                'portalUrl' => route('portal.jobs.index', $this->application->job->company->slug),
            ],
        );
    }
}
