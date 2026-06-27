<?php

namespace App\Services;

use App\Enums\AiRequestStatus;
use App\Enums\AiRequestType;
use App\Enums\TherapySessionStatus;
use App\Enums\VideoRecordingStatus;
use App\Models\TherapySessionVideoCall;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SessionVideoProcessingService
{
    public function __construct(
        private readonly AiAssistantService $aiAssistantService,
    ) {}

    public function process(TherapySessionVideoCall $videoCall): void
    {
        $videoCall->loadMissing('therapySession.patient', 'therapySession.professional');

        $session = $videoCall->therapySession;
        $professional = $session->professional;
        $patient = $session->patient;

        if (! $videoCall->recording_path || ! $videoCall->recording_disk) {
            throw new \RuntimeException(__('Gravação não encontrada.'));
        }

        $disk = Storage::disk($videoCall->recording_disk);
        if (! $disk->exists($videoCall->recording_path)) {
            throw new \RuntimeException(__('Arquivo de gravação indisponível.'));
        }

        $videoCall->update([
            'recording_status' => VideoRecordingStatus::Processing,
            'processing_error' => null,
        ]);

        try {
            $absolutePath = $disk->path($videoCall->recording_path);
            $mime = $disk->mimeType($videoCall->recording_path) ?: 'audio/webm';
            $uploaded = new UploadedFile(
                $absolutePath,
                basename($videoCall->recording_path),
                $mime,
                null,
                true,
            );

            $transcription = $this->aiAssistantService->transcribeAudio(
                $uploaded,
                'retorno',
                $patient?->name,
            );

            $transcriptionRequest = $this->aiAssistantService->persistRequest(
                $professional,
                AiRequestType::Transcricao,
                json_encode(['source' => 'session_video', 'therapy_session_id' => $session->id], JSON_UNESCAPED_UNICODE),
                $transcription['text'],
                null,
                AiRequestStatus::Completed,
                $transcription['tokens_used'],
                $patient?->id,
                $videoCall->recording_consent_at,
                $videoCall->recording_consent_ip,
            );

            $approach = $videoCall->approach ?: 'tcc';

            $clinicalSummary = $this->aiAssistantService->generateTextByApproach(
                $transcription['text'],
                $approach,
                'resumo_clinico',
            );

            $devolutiva = $this->aiAssistantService->generateTextByApproach(
                $transcription['text'],
                $approach,
                'devolutiva_paciente',
            );

            $devolutivaRequest = $this->aiAssistantService->persistRequest(
                $professional,
                AiRequestType::TextoAbordagem,
                \Illuminate\Support\Str::limit($transcription['text'], 8000),
                $devolutiva['text'],
                $approach,
                AiRequestStatus::Completed,
                $devolutiva['tokens_used'],
                $patient?->id,
            );

            $videoCall->update([
                'transcription_text' => $transcription['text'],
                'clinical_summary_text' => $clinicalSummary['text'],
                'devolutiva_patient_text' => $devolutiva['text'],
                'transcription_ai_request_id' => $transcriptionRequest->id,
                'devolutiva_ai_request_id' => $devolutivaRequest->id,
                'recording_status' => VideoRecordingStatus::Completed,
                'processing_error' => null,
            ]);

            if ($session->status === TherapySessionStatus::Scheduled) {
                $session->update(['status' => TherapySessionStatus::Completed]);
            }
        } catch (Throwable $e) {
            report($e);

            $videoCall->update([
                'recording_status' => VideoRecordingStatus::Failed,
                'processing_error' => AiAssistantService::userFacingErrorMessage($e),
            ]);

            throw $e;
        }
    }

    public function regenerateDevolutiva(TherapySessionVideoCall $videoCall, string $approach): void
    {
        $videoCall->loadMissing('therapySession.patient', 'therapySession.professional');

        if (! $videoCall->transcription_text) {
            throw new \RuntimeException(__('Transcreva a sessão antes de gerar a devolutiva.'));
        }

        $result = $this->aiAssistantService->generateTextByApproach(
            $videoCall->transcription_text,
            $approach,
            'devolutiva_paciente',
        );

        $aiRequest = $this->aiAssistantService->persistRequest(
            $videoCall->therapySession->professional,
            AiRequestType::TextoAbordagem,
            \Illuminate\Support\Str::limit($videoCall->transcription_text, 8000),
            $result['text'],
            $approach,
            AiRequestStatus::Completed,
            $result['tokens_used'],
            $videoCall->therapySession->patient_id,
        );

        $videoCall->update([
            'approach' => $approach,
            'devolutiva_patient_text' => $result['text'],
            'devolutiva_ai_request_id' => $aiRequest->id,
        ]);
    }
}
