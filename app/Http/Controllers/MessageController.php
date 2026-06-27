<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\ConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MessageController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversations,
    ) {}

    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('conversations.index', $request->query());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_id' => ['required', 'integer'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $user = $request->user();
        $recipient = \App\Models\User::query()->findOrFail((int) $validated['recipient_id']);

        if ($user->isProfessional()) {
            if (! $this->conversations->patientUserIsEligible($user, $recipient)) {
                throw ValidationException::withMessages([
                    'recipient_id' => __('Destinatário inválido ou sem permissão para enviar.'),
                ]);
            }

            $conversation = $this->conversations->findOrCreateForUsers(
                $user,
                $recipient,
                $this->conversations->resolvePatientRecord($user, $recipient),
            );
        } elseif ($user->isPatient()) {
            $professional = $this->conversations->resolveProfessionalForPatientUser($user);
            if ($professional === null || $recipient->id !== $professional->id) {
                throw ValidationException::withMessages([
                    'recipient_id' => __('Destinatário inválido.'),
                ]);
            }

            $conversation = $this->conversations->findOrCreateForUsers(
                $professional,
                $user,
                $this->conversations->resolvePatientRecord($professional, $user),
            );
        } else {
            abort(403);
        }

        $this->conversations->sendMessage($conversation, $user, $validated['body']);

        return redirect()
            ->route('conversations.show', $conversation)
            ->with('status', __('Mensagem enviada.'));
    }
}
