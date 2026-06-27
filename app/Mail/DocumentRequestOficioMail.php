<?php

namespace App\Mail;

use App\Models\DocumentRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentRequestOficioMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DocumentRequest $documentRequest,
        public User $professional,
        public string $pdfBinary,
        public string $pdfFilename,
        public ?string $customMessage = null,
    ) {}

    public function envelope(): Envelope
    {
        $appName = (string) config('app.name', 'PsiConecta');
        $patientName = $this->documentRequest->patient->name;

        return new Envelope(
            from: new Address(
                (string) config('mail.from.address'),
                $this->professional->name.' — '.$appName,
            ),
            replyTo: [new Address($this->professional->email, $this->professional->name)],
            subject: __('Solicitação de documentos — :patient', ['patient' => $patientName]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document-request-oficio',
            text: 'emails.document-request-oficio-text',
            with: [
                'documentRequest' => $this->documentRequest,
                'professional' => $this->professional,
                'customMessage' => $this->customMessage,
                'appName' => config('app.name'),
            ],
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfBinary, $this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}
