<?php

namespace App\Mail;

use App\Models\DataSubjectRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DataSubjectRequestResolvedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DataSubjectRequest $dataSubjectRequest,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('compliance.lgpd.dpo_name', config('app.name')),
            ),
            subject: __('Atualização da sua solicitação LGPD — :status', [
                'status' => $this->dataSubjectRequest->status->label(),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.data-subject-request-resolved',
            text: 'emails.data-subject-request-resolved-text',
            with: [
                'dataSubjectRequest' => $this->dataSubjectRequest,
                'appName' => config('app.name'),
                'portalUrl' => route('patient.lgpd.index'),
            ],
        );
    }
}
