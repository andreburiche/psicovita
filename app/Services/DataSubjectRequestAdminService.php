<?php

namespace App\Services;

use App\Enums\DataSubjectRequestStatus;
use App\Mail\DataSubjectRequestResolvedMail;
use App\Models\DataSubjectRequest;
use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Support\Facades\Mail;

class DataSubjectRequestAdminService
{
    public function update(DataSubjectRequest $request, User $actor, array $data): DataSubjectRequest
    {
        $status = DataSubjectRequestStatus::from($data['status']);
        $previousStatus = $request->status;

        $request->status = $status;
        $request->response_notes = filled($data['response_notes'] ?? null)
            ? trim((string) $data['response_notes'])
            : null;

        if (in_array($status, [DataSubjectRequestStatus::Completed, DataSubjectRequestStatus::Rejected], true)) {
            $request->completed_at = now();
        } elseif ($status === DataSubjectRequestStatus::Pending) {
            $request->completed_at = null;
        }

        $request->save();

        AuditTrail::entity('lgpd_request_update', 'data_subject_request', $request, [
            'previous_status' => $previousStatus->value,
            'status' => $status->value,
        ], $actor);

        if (
            $status !== $previousStatus
            && in_array($status, [DataSubjectRequestStatus::Completed, DataSubjectRequestStatus::Rejected], true)
        ) {
            $this->notifyRequester($request);
        }

        return $request->fresh(['user', 'patient']);
    }

    private function notifyRequester(DataSubjectRequest $request): void
    {
        $request->loadMissing('user');
        $user = $request->user;

        if ($user === null) {
            return;
        }

        try {
            Mail::to($user->email)->send(new DataSubjectRequestResolvedMail($request));
        } catch (\Throwable) {
            // Falha de SMTP não reverte a atualização.
        }
    }
}
