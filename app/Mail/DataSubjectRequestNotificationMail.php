<?php

namespace App\Mail;

use App\Models\DataSubjectRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DataSubjectRequestNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DataSubjectRequest $dataSubjectRequest,
        public User $requester,
    ) {}

    public function envelope(): Envelope
    {
        $appName = (string) config('app.name', 'PsiConecta');

        return new Envelope(
            from: new Address(
                (string) config('mail.from.address'),
                $appName,
            ),
            replyTo: [new Address($this->requester->email, $this->requester->name)],
            subject: __('[LGPD] Nova solicitação do titular — :type', [
                'type' => $this->dataSubjectRequest->type->label(),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.data-subject-request',
            text: 'emails.data-subject-request-text',
            with: [
                'dataSubjectRequest' => $this->dataSubjectRequest,
                'requester' => $this->requester,
                'appName' => config('app.name'),
                'dpoName' => config('compliance.lgpd.dpo_name'),
            ],
        );
    }
}
