<?php

namespace App\Http\Controllers;

use App\Enums\MessageChannel;
use App\Models\ClinicalRecord;
use App\Models\Conversation;
use App\Models\MessageAttachment;
use App\Models\Patient;
use App\Models\RecordAccessLog;
use App\Models\User;
use App\Notifications\WhatsAppConsentReminderNotification;
use App\Services\ConversationExportService;
use App\Services\ConversationService;
use App\Services\ConversationTypingService;
use App\Services\WhatsAppConversationService;
use App\Services\WhatsAppTransactionalService;
use App\Support\ExternalContactUrls;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConversationController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversations,
        private readonly WhatsAppConversationService $whatsApp,
        private readonly WhatsAppTransactionalService $whatsappTransactional,
        private readonly ConversationExportService $export,
        private readonly ConversationTypingService $typing,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Conversation::class);

        $search = $request->string('q')->trim()->toString();
        $inbox = $this->conversations->inboxFor($request->user(), $search !== '' ? $search : null);

        return $this->renderInbox($request, null, $inbox, search: $search);
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $this->authorize('view', $conversation);

        $this->conversations->markAsRead($conversation, $request->user());

        $search = $request->string('q')->trim()->toString();
        $inbox = $this->conversations->inboxFor($request->user(), $search !== '' ? $search : null);
        $messages = $this->conversations->paginateMessages(
            $conversation,
            $search !== '' ? $search : null,
        );

        return $this->renderInbox($request, $conversation, $inbox, $messages, $search);
    }

    public function poll(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'after_id' => ['required', 'integer', 'min:0'],
        ]);

        $user = $request->user();
        $afterId = (int) $validated['after_id'];
        $newMessages = $this->conversations->messagesSince($conversation, $afterId);

        if ($newMessages->isNotEmpty() && $newMessages->contains(fn ($m) => $m->recipient_id === $user->id)) {
            $this->conversations->markAsRead($conversation, $user);
        }

        $inbox = $this->conversations->inboxFor($user);

        return response()->json([
            'messages' => $newMessages->map(fn ($message) => $this->serializeMessage($message, $user))->values(),
            'unread_total' => $inbox->sum(fn (Conversation $c) => $c->unreadCountFor($user)),
            'peer_typing' => $this->typing->isPeerTyping($conversation, $user),
        ]);
    }

    public function typingPulse(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('message', $conversation);

        $this->typing->pulse($conversation, $request->user());

        return response()->json(['ok' => true]);
    }

    public function exportPdf(Request $request, Conversation $conversation)
    {
        $this->authorize('export', $conversation);

        return $this->export->downloadPdf($conversation, $request->user());
    }

    public function saveToClinicalRecord(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('archiveToRecord', $conversation);

        $conversation->loadMissing('patient');
        abort_unless($conversation->patient_id, 404);

        $transcript = $this->export->buildTranscript($conversation);
        $header = '— '.__('Conversa terapêutica').' · '.now()->format('d/m/Y')." —\n\n";
        $header .= __('Transcrição exportada da plataforma. Revisão profissional recomendada.')."\n\n";

        $record = ClinicalRecord::query()->create([
            'patient_id' => $conversation->patient_id,
            'professional_id' => $request->user()->clinicalPracticeId(),
            'content' => $header.$transcript,
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
            ->with('status', __('Conversa arquivada no prontuário.'));
    }

    public function downloadAttachment(Request $request, Conversation $conversation, MessageAttachment $attachment): StreamedResponse
    {
        $this->authorize('view', $conversation);

        abort_unless($attachment->message?->conversation_id === $conversation->id, 404);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    public function start(Request $request): RedirectResponse
    {
        $this->authorize('create', Conversation::class);

        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
        ]);

        abort_unless($request->user()->isProfessional(), 403);

        $patient = Patient::query()->findOrFail((int) $validated['patient_id']);
        $this->authorize('startWithPatient', [Conversation::class, $patient]);

        $patientUser = $this->conversations->resolvePortalUserForPatient($patient, $request->user());

        if ($patientUser === null) {
            throw ValidationException::withMessages([
                'patient_id' => __('Este paciente ainda não tem conta na plataforma. Crie o acesso na ficha do paciente.'),
            ]);
        }

        $conversation = $this->conversations->findOrCreateForUsers($request->user(), $patientUser, $patient);

        return redirect()->route('conversations.show', $conversation);
    }

    public function startFromPatient(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorize('startWithPatient', [Conversation::class, $patient]);

        $patientUser = $this->conversations->resolvePortalUserForPatient($patient, $request->user());

        if ($patientUser === null) {
            return redirect()
                ->route('patients.show', $patient)
                ->with('error', __('Este paciente ainda não tem conta na plataforma para conversas internas. Crie o acesso ao portal na ficha do paciente.'));
        }

        $conversation = $this->conversations->findOrCreateForUsers($request->user(), $patientUser, $patient);

        return redirect()->route('conversations.show', $conversation);
    }

    public function storeMessage(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('message', $conversation);

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimetypes:application/pdf,image/jpeg,image/png,image/webp,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'mirror_whatsapp' => ['sometimes', 'boolean'],
        ]);

        $mirror = $conversation->canSyncWhatsApp()
            && $request->user()->id === $conversation->professional_id
            && $this->whatsApp->isConfigured()
            && $request->boolean('mirror_whatsapp', true);

        if ($mirror && $this->whatsApp->patientPhoneDigits($conversation) === null) {
            return back()
                ->withInput()
                ->with('error', __('Não foi possível enviar pelo WhatsApp: o paciente não tem telefone na ficha. Adicione o número em Pacientes → editar contacto.'));
        }

        $message = $this->conversations->sendMessage(
            $conversation,
            $request->user(),
            (string) ($validated['body'] ?? ''),
            MessageChannel::Internal,
            mirrorToWhatsApp: $mirror,
            attachment: $request->file('attachment'),
        );

        if ($mirror && $message->external_id) {
            return redirect()
                ->route('conversations.show', $conversation)
                ->with('status', __('Mensagem enviada e replicada no WhatsApp.'));
        }

        if ($mirror && ! $message->external_id) {
            return redirect()
                ->route('conversations.show', $conversation)
                ->with('warning', __('Mensagem guardada na plataforma, mas a Evolution API não confirmou o envio ao WhatsApp. Verifique a conexão em Admin → WhatsApp API.'));
        }

        return redirect()
            ->route('conversations.show', $conversation)
            ->with('status', __('Mensagem enviada.'));
    }

    public function toggleWhatsApp(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('view', $conversation);

        if ($request->user()->id !== $conversation->professional_id) {
            abort(403);
        }

        if (! $this->whatsApp->isConfigured()) {
            return back()->with('error', __('Integração WhatsApp não configurada.'));
        }

        $enabling = ! $conversation->whatsapp_enabled;

        if ($enabling && ! $conversation->hasWhatsappConsent()) {
            $conversation->update(['whatsapp_enabled' => true]);

            return back()->with('status', __('WhatsApp preparado — aguarda consentimento do paciente para sincronizar.'));
        }

        $conversation->update([
            'whatsapp_enabled' => $enabling,
        ]);

        return back()->with('status', $conversation->whatsapp_enabled
            ? __('WhatsApp activado nesta conversa.')
            : __('WhatsApp desactivado nesta conversa.'));
    }

    public function grantWhatsappConsent(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('view', $conversation);

        $this->conversations->recordWhatsappConsent($conversation, $request->user());

        return back()->with('status', __('Consentimento para WhatsApp registado.'));
    }

    public function revokeWhatsappConsent(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('view', $conversation);

        $this->conversations->revokeWhatsappConsent($conversation, $request->user());

        return back()->with('status', __('Consentimento WhatsApp revogado.'));
    }

    public function remindWhatsappConsent(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('view', $conversation);

        if ($request->user()->id !== $conversation->professional_id) {
            abort(403);
        }

        if (! $conversation->whatsapp_enabled || $conversation->hasWhatsappConsent()) {
            return back()->with('error', __('Não é necessário pedir consentimento neste momento.'));
        }

        $patientUser = $conversation->patientUser;
        if ($patientUser === null) {
            return back()->with('error', __('O paciente precisa de conta no portal para consentir.'));
        }

        $sendEmail = $request->boolean('send_email', true);
        $sendWhatsApp = $request->boolean('send_whatsapp', true);
        $emailSent = false;
        $whatsappSent = false;

        if ($sendEmail && filled($patientUser->email)) {
            $patientUser->notify(new WhatsAppConsentReminderNotification($conversation, $request->user()));
            $emailSent = true;
        }

        if ($sendWhatsApp && $this->whatsappTransactional->conversationPatientHasPhone($conversation)) {
            $whatsappSent = $this->whatsappTransactional->sendConsentReminder($conversation, $request->user()) !== null;
        }

        if (! $emailSent && ! $whatsappSent) {
            return back()->with('error', __('Não foi possível enviar o lembrete. Verifique e-mail e telefone na ficha do paciente.'));
        }

        $message = match (true) {
            $emailSent && $whatsappSent => __('Lembrete enviado por e-mail e WhatsApp. O paciente deve consentir dentro da aplicação.'),
            $emailSent => __('Lembrete enviado por e-mail. O paciente deve abrir a conversa e tocar em «Consentir».'),
            default => __('Lembrete enviado por WhatsApp. O paciente deve abrir o link e consentir na aplicação.'),
        };

        return back()->with('status', $message);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Conversation>  $inbox
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator|null  $messages
     */
    private function renderInbox(
        Request $request,
        ?Conversation $active,
        $inbox,
        $messages = null,
        string $search = '',
    ): View {
        $user = $request->user();
        $patientPortal = $user->usesPatientPortalExperience();

        $eligiblePatients = $user->isProfessional()
            ? $this->conversations->listPatientsForConversationPicker($user)
            : collect();

        $whatsappUrl = null;
        $whatsappPhoneMissing = false;
        $whatsappAwaitingConsent = false;
        $consentReminderCanEmail = false;
        $consentReminderCanWhatsApp = false;
        $patientWhatsappAwaitingConsent = false;

        if ($active && $user->isProfessional()) {
            $phone = $active->patient?->phone ?: $active->patientUser?->phone;
            $whatsappPhoneMissing = $active->canSyncWhatsApp() && blank($phone);
            $whatsappUrl = ExternalContactUrls::whatsapp(
                $phone,
                __('Olá :name,', ['name' => $active->patientUser?->name ?? ''])
            );
            $whatsappAwaitingConsent = $active->whatsapp_enabled && ! $active->hasWhatsappConsent();
            $consentReminderCanEmail = filled($active->patientUser?->email);
            $consentReminderCanWhatsApp = $this->whatsappTransactional->conversationPatientHasPhone($active);
        }

        if ($active && $user->isPatient()) {
            $patientWhatsappAwaitingConsent = $active->whatsapp_enabled && ! $active->hasWhatsappConsent();
        }

        return view('conversations.index', [
            'inbox' => $inbox,
            'activeConversation' => $active,
            'messages' => $messages,
            'eligiblePatients' => $eligiblePatients,
            'patientPortal' => $patientPortal,
            'totalUnread' => $inbox->sum(fn (Conversation $c) => $c->unreadCountFor($user)),
            'whatsappConfigured' => $this->whatsApp->isConfigured(),
            'whatsappDriverLabel' => $this->whatsApp->driverLabel(),
            'whatsappUrl' => $whatsappUrl,
            'whatsappPhoneMissing' => $whatsappPhoneMissing,
            'whatsappAwaitingConsent' => $whatsappAwaitingConsent,
            'consentReminderCanEmail' => $consentReminderCanEmail,
            'consentReminderCanWhatsApp' => $consentReminderCanWhatsApp,
            'patientWhatsappAwaitingConsent' => $patientWhatsappAwaitingConsent,
            'search' => $search,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMessage(\App\Models\Message $message, User $user): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'mine' => $message->isFrom($user),
            'sender_name' => $message->sender?->name,
            'created_at' => $message->created_at->format('d/m/Y H:i'),
            'channel' => $message->channel->value,
            'whatsapp_sent' => $message->external_id !== null,
            'read_at' => $message->read_at?->format('d/m/Y H:i'),
            'attachments' => $message->attachments->map(fn (MessageAttachment $a) => [
                'id' => $a->id,
                'name' => $a->original_name,
                'size' => $a->humanSize(),
                'url' => route('conversations.attachments.download', [
                    'conversation' => $message->conversation_id,
                    'attachment' => $a->id,
                ]),
            ])->values(),
        ];
    }
}
