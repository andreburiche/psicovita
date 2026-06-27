<?php

namespace App\Services;

use App\Enums\DocumentRequestStatus;
use App\Mail\DocumentRequestOficioMail;
use App\Models\DocumentRequest;
use App\Models\DocumentRequestAccessLog;
use App\Models\User;
use App\Repositories\Contracts\DocumentRequestRepositoryInterface;
use App\Support\AuditTrail;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class DocumentRequestEmailService
{
    public function __construct(
        private readonly DocumentRequestPdfService $pdfService,
        private readonly DocumentRequestAccessLogService $accessLog,
        private readonly DocumentRequestRepositoryInterface $requests,
    ) {}

    /**
     * @param  array{to: string, cc?: string|null, message?: string|null}  $data
     */
    public function send(DocumentRequest $documentRequest, User $actor, array $data): DocumentRequest
    {
        $to = strtolower(trim($data['to']));
        $cc = filled($data['cc'] ?? null) ? strtolower(trim((string) $data['cc'])) : null;
        $message = filled($data['message'] ?? null) ? trim((string) $data['message']) : null;

        $documentRequest->loadMissing(['patient']);

        $pdfBinary = $this->pdfService->rawOutput($documentRequest, $actor);
        $pdfFilename = $this->pdfService->filename($documentRequest);

        $mailable = new DocumentRequestOficioMail(
            $documentRequest,
            $actor,
            $pdfBinary,
            $pdfFilename,
            $message,
        );

        $pending = Mail::to($to);
        if ($cc) {
            $pending->cc($cc);
        }

        try {
            $pending->send($mailable);
        } catch (\Throwable $e) {
            throw new RuntimeException(__('Não foi possível enviar o e-mail. Verifique as configurações de SMTP no servidor.'), 0, $e);
        }

        $payload = [
            'status' => DocumentRequestStatus::Sent,
            'last_email_sent_at' => now(),
            'last_email_sent_to' => $to,
            'last_email_sent_by' => $actor->id,
            'updated_by' => $actor->id,
        ];

        $request = $this->requests->update($documentRequest, $payload);

        $this->accessLog->record($request, DocumentRequestAccessLog::ACTION_EMAIL_SENT, $actor);
        AuditTrail::entity('email_sent', 'document_requests', $request, [
            'to' => $to,
            'cc' => $cc,
        ], $actor);

        return $request->fresh(['patient', 'createdByUser', 'updatedByUser']);
    }
}
