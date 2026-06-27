<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Patient;
use App\Models\User;
use App\Support\AuditTrail;
use App\Support\PatientAccountResolver;

class PatientDataExportService
{
    public function __construct(
        private readonly PatientAccountResolver $accountResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(User $user): array
    {
        $patients = $this->accountResolver->patientsForUser($user);

        return [
            'schema_version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'app' => config('app.name'),
            'account' => $this->accountSection($user),
            'patients' => $patients->map(fn (Patient $patient) => $this->patientSection($patient, $user))->values()->all(),
            'disclaimer' => __('Prontuário, anamnese e notas clínicas do profissional não são incluídos nesta exportação. Para esses dados, utilize uma solicitação formal ao encarregado.'),
        ];
    }

    public function exportJson(User $user): string
    {
        $payload = $this->buildPayload($user);

        AuditTrail::entity('export', 'patient_data', $user, [
            'patients_count' => count($payload['patients']),
        ], $user);

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function filename(User $user): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($user->normalizedEmail())) ?: 'conta';

        return 'dados-pessoais-'.$slug.'-'.now()->format('Y-m-d').'.json';
    }

    /**
     * @return array<string, mixed>
     */
    private function accountSection(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role?->value,
            'created_at' => $user->created_at?->toIso8601String(),
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function patientSection(Patient $patient, User $user): array
    {
        $patient->loadMissing([
            'professional:id,name,email',
            'therapySessions' => fn ($q) => $q->orderByDesc('session_date')->orderByDesc('id'),
            'payments' => fn ($q) => $q->orderByDesc('created_at')->orderByDesc('id'),
            'documentRequests' => fn ($q) => $q->orderByDesc('request_date')->orderByDesc('id'),
            'documents' => fn ($q) => $q->orderByDesc('received_at')->orderByDesc('id'),
        ]);

        return [
            'id' => $patient->id,
            'professional' => $patient->professional ? [
                'id' => $patient->professional->id,
                'name' => $patient->professional->name,
                'email' => $patient->professional->email,
            ] : null,
            'profile' => [
                'name' => $patient->name,
                'email' => $patient->email,
                'phone' => $patient->phone,
                'birth_date' => $patient->birth_date?->format('Y-m-d'),
                'cpf' => $patient->cpf,
                'address' => [
                    'postal_code' => $patient->address_postal_code,
                    'street' => $patient->address_street,
                    'number' => $patient->address_number,
                    'complement' => $patient->address_complement,
                    'district' => $patient->address_district,
                    'city' => $patient->address_city,
                    'state' => $patient->address_state,
                ],
            ],
            'therapy_sessions' => $patient->therapySessions->map(fn ($s) => [
                'id' => $s->id,
                'date' => $s->session_date?->format('Y-m-d'),
                'time' => $s->session_time,
                'status' => $s->status?->value,
                'type' => $s->type?->value,
            ])->values()->all(),
            'payments' => $patient->payments->map(fn ($p) => [
                'id' => $p->id,
                'amount' => $p->amount,
                'status' => $p->status?->value,
                'payment_method' => $p->payment_method?->value,
                'created_at' => $p->created_at?->toIso8601String(),
            ])->values()->all(),
            'document_requests' => $patient->documentRequests->map(fn ($d) => [
                'id' => $d->id,
                'institution_name' => $d->institution_name,
                'status' => $d->status?->value,
                'request_date' => $d->request_date?->format('Y-m-d'),
                'patient_consent_at' => $d->patient_consent_at?->toIso8601String(),
            ])->values()->all(),
            'documents' => $patient->documents->map(fn ($d) => [
                'id' => $d->id,
                'title' => $d->title,
                'category' => $d->category?->value,
                'original_name' => $d->original_name,
                'received_at' => $d->received_at?->format('Y-m-d'),
                'size_bytes' => $d->size_bytes,
            ])->values()->all(),
            'messages' => $this->messagesForPatient($patient, $user),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function messagesForPatient(Patient $patient, User $user): array
    {
        $professionalId = $patient->professional_id;
        if ($professionalId === null) {
            return [];
        }

        return Message::query()
            ->where(function ($q) use ($user, $professionalId) {
                $q->where(fn ($q2) => $q2->where('sender_id', $user->id)->where('recipient_id', $professionalId))
                    ->orWhere(fn ($q2) => $q2->where('sender_id', $professionalId)->where('recipient_id', $user->id));
            })
            ->orderBy('created_at')
            ->get()
            ->map(fn (Message $m) => [
                'id' => $m->id,
                'direction' => $m->sender_id === $user->id ? 'sent' : 'received',
                'body' => $m->body,
                'read_at' => $m->read_at?->toIso8601String(),
                'created_at' => $m->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
