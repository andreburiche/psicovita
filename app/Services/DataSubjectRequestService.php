<?php

namespace App\Services;

use App\Enums\DataSubjectRequestStatus;
use App\Enums\DataSubjectRequestType;
use App\Mail\DataSubjectRequestNotificationMail;
use App\Models\DataSubjectRequest;
use App\Models\User;
use App\Support\AuditTrail;
use App\Support\PatientAccountResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class DataSubjectRequestService
{
    public function __construct(
        private readonly PatientAccountResolver $accountResolver,
    ) {}

    /**
     * @param  array{type: string, details?: string|null, patient_id?: int|null}  $data
     */
    public function create(User $user, array $data, ?Request $request = null): DataSubjectRequest
    {
        $request ??= request();

        $type = DataSubjectRequestType::from($data['type']);
        $patient = $this->accountResolver->resolvePatientForUser(
            $user,
            isset($data['patient_id']) ? (int) $data['patient_id'] : null,
        );

        $patients = $this->accountResolver->patientsForUser($user);
        if ($patients->count() > 1 && $patient === null) {
            throw new RuntimeException(__('Selecione a ficha vinculada à sua solicitação.'));
        }

        $record = DataSubjectRequest::query()->create([
            'user_id' => $user->id,
            'patient_id' => $patient?->id,
            'type' => $type,
            'status' => DataSubjectRequestStatus::Pending,
            'details' => filled($data['details'] ?? null) ? trim((string) $data['details']) : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);

        AuditTrail::entity('lgpd_request', 'data_subject_request', $record, [
            'type' => $type->value,
            'patient_id' => $patient?->id,
        ], $user);

        $this->notifyDpo($record, $user);

        return $record;
    }

    private function notifyDpo(DataSubjectRequest $record, User $user): void
    {
        $dpoEmail = (string) config('compliance.lgpd.dpo_email');
        if ($dpoEmail === '') {
            return;
        }

        try {
            Mail::to($dpoEmail)->send(new DataSubjectRequestNotificationMail($record, $user));
        } catch (\Throwable) {
            // Falha de SMTP não impede o registro da solicitação.
        }
    }
}
