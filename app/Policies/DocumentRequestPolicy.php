<?php

namespace App\Policies;

use App\Models\DocumentRequest;
use App\Models\Patient;
use App\Models\User;
use App\Support\Permissions;

class DocumentRequestPolicy
{
    public function viewAny(User $user, Patient $patient): bool
    {
        return $user->hasPermission(Permissions::SOLICITACOES_VISUALIZAR)
            && $this->ownsPatient($user, $patient);
    }

    public function view(User $user, DocumentRequest $documentRequest): bool
    {
        return $user->hasPermission(Permissions::SOLICITACOES_VISUALIZAR)
            && $this->ownsPatient($user, $documentRequest->patient);
    }

    public function create(User $user, Patient $patient): bool
    {
        return $user->hasPermission(Permissions::SOLICITACOES_CRIAR)
            && $this->ownsPatient($user, $patient);
    }

    public function update(User $user, DocumentRequest $documentRequest): bool
    {
        return $user->hasPermission(Permissions::SOLICITACOES_EDITAR)
            && $this->ownsPatient($user, $documentRequest->patient);
    }

    public function delete(User $user, DocumentRequest $documentRequest): bool
    {
        return $user->hasPermission(Permissions::SOLICITACOES_EXCLUIR)
            && $this->ownsPatient($user, $documentRequest->patient);
    }

    public function downloadPdf(User $user, DocumentRequest $documentRequest): bool
    {
        return $user->hasPermission(Permissions::SOLICITACOES_BAIXAR_PDF)
            && $this->ownsPatient($user, $documentRequest->patient);
    }

    public function uploadFile(User $user, DocumentRequest $documentRequest): bool
    {
        return $user->hasPermission(Permissions::SOLICITACOES_EDITAR)
            && $this->ownsPatient($user, $documentRequest->patient);
    }

    public function sendEmail(User $user, DocumentRequest $documentRequest): bool
    {
        return $user->hasPermission(Permissions::SOLICITACOES_ENVIAR_EMAIL)
            && $this->ownsPatient($user, $documentRequest->patient);
    }

    private function ownsPatient(User $user, Patient $patient): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isProfessional() && (int) $patient->professional_id === $user->clinicalPracticeId();
    }
}
