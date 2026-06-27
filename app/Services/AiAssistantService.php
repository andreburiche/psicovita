<?php

namespace App\Services;

use App\Enums\AiRequestStatus;
use App\Enums\AiRequestType;
use App\Models\AiRequest;
use App\Models\User;
use GuzzleHttp\Exception\ConnectException as GuzzleConnectException;
use Illuminate\Http\Client\ConnectionException as HttpConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AiAssistantService
{
    public function isApiConfigured(): bool
    {
        if (! (bool) Config::get('psiconecta.ai.enabled', true)) {
            return false;
        }

        return $this->llmChatEndpointReady();
    }

    /**
     * Há um endpoint de chat (OpenAI na nuvem ou Ollama local) disponível para texto/recomendação.
     */
    public function llmChatEndpointReady(): bool
    {
        $provider = $this->provider();

        if ($provider === 'mock') {
            return false;
        }

        if ($provider === 'ollama') {
            return true;
        }

        $key = Config::get('psiconecta.ai.openai_api_key');

        return is_string($key) && trim($key) !== '';
    }

    /**
     * Transcrição real via API OpenAI (Whisper). Ollama/mock usam simulação na transcrição.
     */
    public function openAiTranscriptionReady(): bool
    {
        if (! (bool) Config::get('psiconecta.ai.enabled', true)) {
            return false;
        }

        if ($this->provider() !== 'openai') {
            return false;
        }

        $key = Config::get('psiconecta.ai.openai_api_key');

        return is_string($key) && trim($key) !== '';
    }

    private function provider(): string
    {
        $p = strtolower((string) Config::get('psiconecta.ai.provider', 'openai'));

        return in_array($p, ['openai', 'ollama', 'mock'], true) ? $p : 'openai';
    }

    /**
     * URL base do endpoint /v1/chat/completions (OpenAI ou Ollama em modo compatível).
     */
    private function chatApiBaseUrl(): string
    {
        $raw = (string) Config::get('psiconecta.ai.openai_base_url', 'https://api.openai.com/v1');
        $base = rtrim($raw, '/');

        if ($this->provider() === 'ollama' && str_contains($base, 'api.openai.com')) {
            return 'http://127.0.0.1:11434/v1';
        }

        return $base !== '' ? $base : 'https://api.openai.com/v1';
    }

    /**
     * Mensagem segura para o utilizador (sem expor chaves ou detalhes internos).
     */
    public static function userFacingErrorMessage(Throwable $e): string
    {
        $connectionLostMessage = __(
            'Não foi possível ligar ao servidor de modelo (ex.: Ollama em :url). Inicie o Ollama no Windows (menu Iniciar ou ícone na bandeja), confirme `ollama serve` ou que a porta 11434 está ativa, e verifique OPENAI_BASE_URL no .env.',
            ['url' => 'http://127.0.0.1:11434/v1'],
        );

        for ($ex = $e; $ex !== null; $ex = $ex->getPrevious()) {
            if ($ex instanceof HttpConnectionException || $ex instanceof GuzzleConnectException) {
                return $connectionLostMessage;
            }
        }

        $m = $e->getMessage();

        if (stripos($m, 'cURL error 7') !== false
            || stripos($m, "Couldn't connect to server") !== false
            || (stripos($m, 'Failed to connect') !== false && stripos($m, '11434') !== false)) {
            return $connectionLostMessage;
        }

        if (stripos($m, 'exceeded your current quota') !== false
            || stripos($m, 'insufficient_quota') !== false
            || stripos($m, 'Billing hard limit') !== false) {
            return __('A conta OpenAI não tem cota disponível ou precisa de faturação ativa. Confirme em platform.openai.com/settings/billing.');
        }

        if (stripos($m, 'Incorrect API key') !== false
            || stripos($m, 'invalid_api_key') !== false
            || stripos($m, 'invalid x-api-key') !== false) {
            return __('A chave OPENAI_API_KEY parece inválida ou revogada. Gere uma nova chave, atualize o .env e execute php artisan config:clear.');
        }

        if (stripos($m, 'rate_limit') !== false || stripos($m, '429') !== false) {
            return __('Limite de pedidos da OpenAI. Aguarde alguns minutos e tente novamente.');
        }

        if (str_contains($m, 'Chave OpenAI não configurada')) {
            return __('Defina OPENAI_API_KEY no .env e execute php artisan config:clear.');
        }

        if (preg_match("/model ['\"]([^'\"]+)['\"] not found/i", $m, $modelMissing)) {
            return __(
                'O modelo ":model" não está disponível no Ollama. Descarregue-o com: ollama pull :model — ou altere OPENAI_CHAT_MODEL no .env para um modelo que já exista (veja ollama list). Depois execute php artisan config:clear.',
                ['model' => $modelMissing[1]],
            );
        }

        if (str_starts_with($m, 'OpenAI (') || str_starts_with($m, 'LLM (')) {
            return __('O serviço de modelo devolveu um erro. Se usa OpenAI, verifique conta e limites; se usa Ollama, confira se está em execução e o URL no .env.');
        }

        return __('Ocorreu um erro inesperado. Tente novamente mais tarde.');
    }

    /**
     * @return array{text: string, tokens_used: ?int}
     */
    public function transcribeAudio(?UploadedFile $audioFile, string $sessionType, ?string $patientName): array
    {
        if ($this->openAiTranscriptionReady() && $audioFile !== null) {
            return $this->transcribeWithOpenAi($audioFile, $sessionType, $patientName);
        }

        return $this->transcribeMock($audioFile, $sessionType, $patientName);
    }

    /**
     * @return array{text: string, tokens_used: ?int}
     */
    public function generateTextByApproach(string $text, string $approach, string $outputType): array
    {
        if ($this->llmChatEndpointReady()) {
            return $this->generateTextWithOpenAi($text, $approach, $outputType);
        }

        return $this->generateTextMock($text, $approach, $outputType);
    }

    /**
     * @param  array{complaint: string, modality: string, price_range: string, approach: string, availability: string}  $patientData
     * @return array{ranking: list<array{name: string, specialty: string, approach: string, compatibility: int, justification: string}>, tokens_used: ?int}
     */
    public function recommendTherapist(array $patientData): array
    {
        if ($this->llmChatEndpointReady()) {
            try {
                return $this->recommendWithOpenAi($patientData);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $this->recommendMock($patientData);
    }

    /**
     * @return array{text: string, tokens_used: ?int}
     */
    private function transcribeWithOpenAi(UploadedFile $audioFile, string $sessionType, ?string $patientName): array
    {
        $key = $this->requireOpenAiKeyForTranscription();
        $base = (string) Config::get('psiconecta.ai.openai_base_url', 'https://api.openai.com/v1');
        $model = (string) Config::get('psiconecta.ai.openai_transcribe_model', 'whisper-1');
        $timeout = (int) Config::get('psiconecta.ai.openai_timeout', 120);

        $path = $audioFile->getRealPath();
        if ($path === false || ! is_readable($path)) {
            throw new RuntimeException('Arquivo de áudio inválido ou ilegível.');
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('Não foi possível ler o arquivo de áudio.');
        }

        $filename = $audioFile->getClientOriginalName() ?: 'audio.mp3';

        $response = Http::withToken($key)
            ->timeout($timeout)
            ->connectTimeout(30)
            ->attach('file', $contents, $filename)
            ->post($base.'/audio/transcriptions', [
                'model' => $model,
                'language' => 'pt',
            ]);

        $this->throwUnlessLlmOk($response, 'transcrição');

        $text = (string) ($response->json('text') ?? '');
        $text = trim($text);
        if ($text === '') {
            throw new RuntimeException('A API devolveu transcrição vazia.');
        }

        $footer = "\n\n---\n".__('Contexto informado pelo profissional: tipo de sessão :tipo.', ['tipo' => $sessionType]);
        if ($patientName) {
            $footer .= ' '.(__('Referência ao paciente: :name.', ['name' => $patientName]));
        }
        $footer .= "\n".__('Revise integralmente antes de arquivar e confirme consentimentos (LGPD).');

        return ['text' => $text.$footer, 'tokens_used' => null];
    }

    /**
     * @return array{text: string, tokens_used: ?int}
     */
    private function generateTextWithOpenAi(string $text, string $approach, string $outputType): array
    {
        $prompt = self::internalPromptTextoAbordagem($approach, $outputType, $text);

        $system = 'És um assistente de apoio a profissionais de saúde mental em português (Brasil). '
            .'Nunca substituís avaliação clínica nem faças diagnóstico fechado. '
            .'No início da resposta, inclui uma linha: "Conteúdo gerado por IA — revisão obrigatória pelo profissional." '
            .'Depois, responde só ao pedido do utilizador, com tom ético e acolhedor.';

        return $this->chatCompletionText($system, $prompt);
    }

    /**
     * @param  array{complaint: string, modality: string, price_range: string, approach: string, availability: string}  $patientData
     * @return array{ranking: list<array{name: string, specialty: string, approach: string, compatibility: int, justification: string}>, tokens_used: ?int}
     */
    private function recommendWithOpenAi(array $patientData): array
    {
        $system = 'És um assistente de apoio administrativo (não clínico). Responde em português (Brasil). '
            .'Devolves APENAS um array JSON válido (sem markdown, sem texto extra) com exatamente 3 objetos. '
            .'Cada objeto: {"name": string, "specialty": string, "approach": string, "compatibility": number, "justification": string}. '
            .'Os nomes devem ser exemplos fictícios (ex.: "Dra. Ana Silva (exemplo)"). compatibility entre 0 e 100. '
            .'Não prometas resultado terapêutico. Base: '.self::internalPromptRecomendacaoTerapeuta();

        $userPayload = json_encode($patientData, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $result = $this->chatCompletionText($system, 'Dados para ranking (JSON): '.$userPayload);
        $parsed = $this->extractJsonArray($result['text']);
        if (! is_array($parsed) || $parsed === []) {
            throw new RuntimeException('Resposta da IA em formato inválido.');
        }

        $ranking = [];
        foreach (array_slice($parsed, 0, 5) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $ranking[] = [
                'name' => (string) ($row['name'] ?? 'Profissional (exemplo)'),
                'specialty' => (string) ($row['specialty'] ?? 'Psicologia clínica'),
                'approach' => (string) ($row['approach'] ?? ($patientData['approach'] ?? 'TCC')),
                'compatibility' => max(0, min(100, (int) ($row['compatibility'] ?? 70))),
                'justification' => (string) ($row['justification'] ?? 'Rever encaixe com o paciente.'),
            ];
        }

        if ($ranking === []) {
            throw new RuntimeException('Ranking vazio na resposta da IA.');
        }

        return ['ranking' => $ranking, 'tokens_used' => $result['tokens_used']];
    }

    /**
     * @return array{text: string, tokens_used: ?int}
     */
    public function completeChat(
        string $system,
        string $user,
        int $maxTokens = 800,
        float $temperature = 0.2,
    ): array {
        if (! $this->llmChatEndpointReady()) {
            throw new RuntimeException('LLM não configurado.');
        }

        return $this->chatCompletionText($system, $user, $maxTokens, $temperature);
    }

    /**
     * @return array{text: string, tokens_used: ?int}
     */
    private function chatCompletionText(
        string $system,
        string $user,
        int $maxTokens = 3500,
        float $temperature = 0.35,
    ): array {
        $base = $this->chatApiBaseUrl();
        $model = (string) Config::get('psiconecta.ai.openai_chat_model', 'gpt-4o-mini');
        $timeout = (int) Config::get('psiconecta.ai.openai_timeout', 120);

        $client = Http::timeout($timeout)
            ->connectTimeout(25)
            ->acceptJson();

        $token = $this->resolveChatBearerToken();
        if ($token !== null && $token !== '') {
            $client = $client->withToken($token);
        }

        $response = $client->post($base.'/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ]);

        $this->throwUnlessLlmOk($response, 'chat');

        $content = $response->json('choices.0.message.content');
        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('Resposta vazia do modelo de chat.');
        }

        $tokens = $response->json('usage.total_tokens');
        $tokensUsed = is_numeric($tokens) ? (int) $tokens : null;

        return ['text' => trim($content), 'tokens_used' => $tokensUsed];
    }

    private function throwUnlessLlmOk(Response $response, string $contexto): void
    {
        if ($response->successful()) {
            return;
        }

        $message = $response->json('error.message');
        $message = is_string($message) ? $message : 'Erro HTTP '.$response->status();

        throw new RuntimeException('LLM ('.$contexto.'): '.$message);
    }

    /**
     * Bearer para APIs compatíveis OpenAI. Ollama: use "ollama" se OPENAI_API_KEY estiver vazio.
     */
    private function resolveChatBearerToken(): ?string
    {
        $raw = Config::get('psiconecta.ai.openai_api_key');
        $trimmed = is_string($raw) ? trim($raw) : '';

        if ($this->provider() === 'ollama') {
            return $trimmed !== '' ? $trimmed : 'ollama';
        }

        if ($trimmed === '') {
            throw new RuntimeException('Chave OpenAI não configurada.');
        }

        return $trimmed;
    }

    private function requireOpenAiKeyForTranscription(): string
    {
        $key = Config::get('psiconecta.ai.openai_api_key');
        if (! is_string($key) || trim($key) === '') {
            throw new RuntimeException('Chave OpenAI não configurada.');
        }

        return trim($key);
    }

    /**
     * @return array{text: string, tokens_used: ?int}
     */
    private function transcribeMock(?UploadedFile $audioFile, string $sessionType, ?string $patientName): array
    {
        $original = $audioFile?->getClientOriginalName() ?? 'áudio';

        $text = __('[Simulação — sem API configurada] Transcrição aproximada do arquivo ":file".', ['file' => $original])."\n\n";
        $text .= __('Tipo de sessão indicado: :type.', ['type' => $sessionType]);
        if ($patientName) {
            $text .= ' '.(__('Referência ao paciente: :name.', ['name' => $patientName]));
        }
        $text .= "\n\n".__('Revise integralmente o conteúdo antes de arquivar. Ajuste termos clínicos e confirme consentimentos.');

        return ['text' => $text, 'tokens_used' => null];
    }

    /**
     * @return array{text: string, tokens_used: ?int}
     */
    private function generateTextMock(string $text, string $approach, string $outputType): array
    {
        $prompt = self::internalPromptTextoAbordagem($approach, $outputType, $text);

        $body = __('[Simulação — sem API configurada] Versão de apoio, não substitui avaliação clínica.')."\n\n";
        $body .= __('Prompt interno (referência para futura API):')."\n".$prompt."\n\n---\n\n";
        $body .= Str::limit(strip_tags($text), 1200, "\n[…]")."\n\n";
        $body .= __('Sugestão: reformule com o seu vocabulário teórico, cite quadro legal e ética da ordem profissional, e adapte ao vínculo com o paciente.');

        return ['text' => $body, 'tokens_used' => null];
    }

    /**
     * @param  array{complaint: string, modality: string, price_range: string, approach: string, availability: string}  $patientData
     * @return array{ranking: list<array{name: string, specialty: string, approach: string, compatibility: int, justification: string}>, tokens_used: ?int}
     */
    private function recommendMock(array $patientData): array
    {
        $approach = $patientData['approach'] ?? 'sem_preferencia';
        $displayApproach = in_array($approach, ['sem_preferencia', ''], true) ? 'TCC' : $approach;

        $ranking = [
            [
                'name' => 'Dr(a). Alexandra Mendes (exemplo)',
                'specialty' => 'Psicologia clínica / adultos',
                'approach' => $displayApproach,
                'compatibility' => 88,
                'justification' => 'Alinhamento parcial com a queixa descrita, modalidade '.$patientData['modality'].' e faixa indicada. Ajuste expectativas e combine primeira entrevista.',
            ],
            [
                'name' => 'Dr. Ricardo Alves (exemplo)',
                'specialty' => 'Psicanálise',
                'approach' => 'Lacaniana',
                'compatibility' => 76,
                'justification' => 'Útil se procurar escuta aprofundada; confirme disponibilidade e supervisão. Exemplo ilustrativo sem garantia de resultado.',
            ],
            [
                'name' => 'Dra. Mariana Costa (exemplo)',
                'specialty' => 'Terapia humanista',
                'approach' => 'Humanista',
                'compatibility' => 71,
                'justification' => 'Boa aderência a pedidos de acolhimento; valide presencialmente critérios de encaixe.',
            ],
        ];

        return ['ranking' => $ranking, 'tokens_used' => null];
    }

    private function extractJsonArray(string $content): ?array
    {
        $content = trim($content);
        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $content, $matches)) {
            $content = trim($matches[1]);
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Prompt base para geração por abordagem (substituir variáveis na chamada à API).
     */
    public static function internalPromptTextoAbordagem(string $abordagem, string $tipoSaida, string $texto): string
    {
        return 'Você é um assistente de apoio clínico para profissionais de psicanálise e saúde mental. Analise o conteúdo abaixo com base na abordagem '
            .$abordagem.'. Gere uma resposta do tipo '.$tipoSaida.'. Use linguagem ética, acolhedora e profissional. Não faça diagnóstico fechado. '
            .'Não substitua avaliação profissional. Conteúdo da sessão: '.$texto;
    }

    /**
     * Prompt base para recomendação de terapeutas.
     */
    public static function internalPromptRecomendacaoTerapeuta(): string
    {
        return 'Com base nas informações do paciente, recomende os terapeutas mais compatíveis. Considere abordagem, especialidade, disponibilidade, '
            .'valor e modalidade de atendimento. Retorne um ranking com justificativa clara e percentual de compatibilidade. Não prometa resultado terapêutico.';
    }

    public function persistRequest(
        User $user,
        AiRequestType $type,
        ?string $inputText,
        ?string $outputText,
        ?string $approach,
        AiRequestStatus $status,
        ?int $tokensUsed,
        ?int $patientId = null,
        ?\DateTimeInterface $lgpdConsentAt = null,
        ?string $lgpdConsentIp = null,
    ): AiRequest {
        return AiRequest::query()->create([
            'user_id' => $user->id,
            'patient_id' => $patientId,
            'type' => $type,
            'input_text' => $inputText,
            'output_text' => $outputText,
            'approach' => $approach,
            'status' => $status,
            'tokens_used' => $tokensUsed,
            'lgpd_consent_at' => $lgpdConsentAt,
            'lgpd_consent_ip' => $lgpdConsentIp,
        ]);
    }
}
