<?php

namespace App\Http\Controllers;

use App\Enums\AiRequestStatus;
use App\Enums\AiRequestType;
use App\Models\AiRequest;
use App\Models\ClinicalRecord;
use App\Models\Patient;
use App\Models\RecordAccessLog;
use App\Services\AiAssistantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AiAssistantController extends Controller
{
    public function __construct(
        private readonly AiAssistantService $aiAssistantService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $start = now()->startOfDay();

        $base = AiRequest::query()->where('user_id', $user->id)->where('created_at', '>=', $start);

        $metrics = [
            'analyses_today' => (clone $base)->count(),
            'texts' => (clone $base)->where('type', AiRequestType::TextoAbordagem)->count(),
            'transcripts' => (clone $base)->where('type', AiRequestType::Transcricao)->count(),
            'recommendations' => (clone $base)->where('type', AiRequestType::RecomendacaoTerapeuta)->count(),
        ];

        $recent = AiRequest::query()
            ->where('user_id', $user->id)
            ->with('patient')
            ->latest()
            ->limit(20)
            ->get();

        $patients = Patient::query()
            ->where('professional_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $highlightRequest = null;
        if ($id = $request->session()->pull('last_ai_request_id')) {
            $highlightRequest = AiRequest::query()
                ->where('user_id', $user->id)
                ->with('patient')
                ->find($id);
        }

        return view('ai.index', compact('metrics', 'recent', 'patients', 'highlightRequest'));
    }

    public function show(Request $request, AiRequest $aiRequest): View
    {
        $this->authorize('view', $aiRequest);
        $aiRequest->load('patient');

        $patients = Patient::query()
            ->where('professional_id', $request->user()->clinicalPracticeId())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('ai.show', [
            'aiRequest' => $aiRequest,
            'patients' => $patients,
        ]);
    }

    public function transcribe(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'audio' => ['required', 'file', 'max:51200', 'mimes:mp3,wav,m4a,webm,ogg,mp4,mpeg'],
                'patient_name' => ['nullable', 'string', 'max:200'],
                'patient_id' => ['nullable', Rule::exists('patients', 'id')->where('professional_id', $request->user()->clinicalPracticeId())],
                'session_type' => ['required', Rule::in(['primeira_sessao', 'retorno', 'avaliacao_inicial'])],
                'lgpd_audio_consent' => ['accepted'],
                'return_to' => ['nullable', Rule::in(['clinical-records.create'])],
            ]);
        } catch (ValidationException $e) {
            return $this->redirectAfterAiFailure($request, $e->errors());
        }

        $inputSummary = json_encode([
            'session_type' => $validated['session_type'],
            'patient_name' => $this->sanitize($validated['patient_name'] ?? null),
            'patient_id' => $validated['patient_id'] ?? null,
            'file' => $request->file('audio')?->getClientOriginalName(),
        ], JSON_UNESCAPED_UNICODE);

        $aiRequest = null;

        try {
            $result = $this->aiAssistantService->transcribeAudio(
                $request->file('audio'),
                $validated['session_type'],
                $this->sanitize($validated['patient_name'] ?? null),
            );

            $text = $result['text'];
            $consentAt = now();
            $consentIp = $request->ip();

            $aiRequest = $this->aiAssistantService->persistRequest(
                $request->user(),
                AiRequestType::Transcricao,
                $inputSummary,
                $text,
                null,
                AiRequestStatus::Completed,
                $result['tokens_used'],
                isset($validated['patient_id']) ? (int) $validated['patient_id'] : null,
                $consentAt,
                $consentIp,
            );
        } catch (\Throwable $e) {
            report($e);
            $this->aiAssistantService->persistRequest(
                $request->user(),
                AiRequestType::Transcricao,
                $inputSummary,
                null,
                null,
                AiRequestStatus::Failed,
                null,
                isset($validated['patient_id']) ? (int) $validated['patient_id'] : null,
                now(),
                $request->ip(),
            );

            return $this->redirectAfterAiFailure($request, ['audio' => AiAssistantService::userFacingErrorMessage($e)]);
        }

        return $this->redirectAfterAiSuccess(
            $request,
            $aiRequest,
            __('Transcrição concluída (revisão obrigatória).'),
        );
    }

    public function generateText(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'session_text' => ['required', 'string', 'max:50000'],
                'approach' => ['required', Rule::in([
                    'freudiana', 'lacaniana', 'jungiana', 'winnicottiana', 'humanista', 'tcc', 'sistemica',
                ])],
                'output_type' => ['required', Rule::in([
                    'resumo_clinico', 'devolutiva_paciente', 'orientacao_pos_sessao', 'texto_acolhedor', 'pontos_atencao',
                ])],
                'patient_id' => ['nullable', Rule::exists('patients', 'id')->where('professional_id', $request->user()->clinicalPracticeId())],
                'return_to' => ['nullable', Rule::in(['clinical-records.create'])],
            ]);
        } catch (ValidationException $e) {
            return $this->redirectAfterAiFailure($request, $e->errors());
        }

        $clean = $this->sanitize($validated['session_text']);
        $inputSummary = Str::limit($clean, 8000);

        $aiRequest = null;

        try {
            $result = $this->aiAssistantService->generateTextByApproach(
                $clean,
                $validated['approach'],
                $validated['output_type'],
            );

            $aiRequest = $this->aiAssistantService->persistRequest(
                $request->user(),
                AiRequestType::TextoAbordagem,
                $inputSummary,
                $result['text'],
                $validated['approach'],
                AiRequestStatus::Completed,
                $result['tokens_used'],
                isset($validated['patient_id']) ? (int) $validated['patient_id'] : null,
            );
        } catch (\Throwable $e) {
            report($e);
            $this->aiAssistantService->persistRequest(
                $request->user(),
                AiRequestType::TextoAbordagem,
                $inputSummary,
                null,
                $validated['approach'],
                AiRequestStatus::Failed,
                null,
                isset($validated['patient_id']) ? (int) $validated['patient_id'] : null,
            );

            return $this->redirectAfterAiFailure($request, ['session_text' => AiAssistantService::userFacingErrorMessage($e)]);
        }

        return $this->redirectAfterAiSuccess(
            $request,
            $aiRequest,
            __('Texto gerado (revisão obrigatória).'),
        );
    }

    public function recommendTherapist(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'complaint' => ['required', 'string', 'max:4000'],
            'modality' => ['required', Rule::in(['online', 'presencial', 'ambos'])],
            'price_range' => ['required', Rule::in(['ate_100', 'de_100_200', 'de_200_300', 'acima_300'])],
            'approach' => ['required', Rule::in([
                'sem_preferencia', 'freudiana', 'lacaniana', 'jungiana', 'tcc', 'humanista',
            ])],
            'availability' => ['nullable', 'string', 'max:500'],
        ]);

        $patientData = [
            'complaint' => $this->sanitize($validated['complaint']),
            'modality' => $validated['modality'],
            'price_range' => $validated['price_range'],
            'approach' => $validated['approach'],
            'availability' => $this->sanitize($validated['availability'] ?? ''),
        ];

        $inputSummary = json_encode($patientData, JSON_UNESCAPED_UNICODE);

        $aiRequest = null;

        try {
            $result = $this->aiAssistantService->recommendTherapist($patientData);
            $output = json_encode($result['ranking'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            $aiRequest = $this->aiAssistantService->persistRequest(
                $request->user(),
                AiRequestType::RecomendacaoTerapeuta,
                $inputSummary,
                $output,
                $validated['approach'],
                AiRequestStatus::Completed,
                $result['tokens_used'],
                null,
            );
        } catch (\Throwable $e) {
            report($e);
            $this->aiAssistantService->persistRequest(
                $request->user(),
                AiRequestType::RecomendacaoTerapeuta,
                $inputSummary,
                null,
                $validated['approach'],
                AiRequestStatus::Failed,
                null,
                null,
            );

            return back()->withErrors(['complaint' => AiAssistantService::userFacingErrorMessage($e)]);
        }

        return redirect()
            ->route('ai.index')
            ->withFragment('ultimo-resultado')
            ->with('status', __('Recomendações geradas (exemplos ilustrativos — revisar).'))
            ->with('last_ai_request_id', $aiRequest?->id);
    }

    public function saveToRecord(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ai_request_id' => ['required', Rule::exists('ai_requests', 'id')->where('user_id', $request->user()->id)],
            'patient_id' => ['required', Rule::exists('patients', 'id')->where('professional_id', $request->user()->clinicalPracticeId())],
        ]);

        $aiRequest = AiRequest::query()
            ->where('id', $validated['ai_request_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (! $aiRequest->output_text) {
            return back()->withErrors(['save' => __('Não há conteúdo concluído para guardar.')]);
        }

        $header = "— ".(__('Nota de apoio (IA) — revisão profissional obrigatória'))." —\n\n";
        $body = $aiRequest->type === AiRequestType::RecomendacaoTerapeuta
            ? $this->formatRecommendationForRecord($aiRequest->output_text)
            : $aiRequest->output_text;

        $record = ClinicalRecord::query()->create([
            'patient_id' => (int) $validated['patient_id'],
            'professional_id' => $request->user()->clinicalPracticeId(),
            'content' => $header.$body,
        ]);

        RecordAccessLog::query()->create([
            'user_id' => $request->user()->id,
            'clinical_record_id' => $record->id,
            'action' => RecordAccessLog::ACTION_CREATED,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('clinical-records.show', $record)
            ->with('status', __('Entrada criada no prontuário. Revise antes de considerar definitiva.'));
    }

    public function destroy(Request $request, AiRequest $aiRequest): RedirectResponse
    {
        $this->authorize('delete', $aiRequest);
        $aiRequest->delete();

        return back()->with('status', __('Registro de uso da IA removido.'));
    }

    private function redirectAfterAiSuccess(Request $request, ?AiRequest $aiRequest, string $statusMessage): RedirectResponse
    {
        if ($request->input('return_to') === 'clinical-records.create') {
            return redirect()
                ->route('clinical-records.create')
                ->withFragment('conteudo-prontuario')
                ->with('status', $statusMessage)
                ->with('ai_content', $aiRequest?->output_text)
                ->with('ai_patient_id', $request->input('patient_id'));
        }

        return redirect()
            ->route('ai.index')
            ->withFragment('ultimo-resultado')
            ->with('status', $statusMessage)
            ->with('last_ai_request_id', $aiRequest?->id);
    }

    /**
     * @param  array<string, string|array<int, string>>  $errors
     */
    private function redirectAfterAiFailure(Request $request, array $errors): RedirectResponse
    {
        if ($request->input('return_to') === 'clinical-records.create') {
            return redirect()
                ->route('clinical-records.create')
                ->withFragment('apoio-ia')
                ->withErrors($errors)
                ->withInput($request->except(['audio']));
        }

        return back()->withErrors($errors)->withInput($request->except(['audio']));
    }

    private function sanitize(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = strip_tags($text);
        $text = preg_replace('/\x00/', '', $text) ?? $text;

        return trim($text) === '' ? null : trim($text);
    }

    private function formatRecommendationForRecord(string $json): string
    {
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return $json;
        }

        $lines = [];
        foreach ($decoded as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            $rank = $i + 1;
            $lines[] = "#{$rank} — ".($row['name'] ?? '').' — '.($row['compatibility'] ?? '').'%';
            $lines[] = ($row['specialty'] ?? '').' | '.($row['approach'] ?? '');
            $lines[] = $row['justification'] ?? '';
            $lines[] = '';
        }

        return implode("\n", $lines);
    }
}
