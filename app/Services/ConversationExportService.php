<?php

namespace App\Services;

use App\Enums\MessageChannel;
use App\Models\Conversation;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ConversationExportService
{
    public function buildTranscript(Conversation $conversation): string
    {
        $conversation->loadMissing(['professional', 'patientUser', 'patient']);

        $messages = $conversation->messages()
            ->with(['sender', 'attachments'])
            ->orderBy('created_at')
            ->get();

        $peerName = $conversation->patient?->name
            ?? $conversation->patientUser?->name
            ?? __('Paciente');

        $lines = [
            '=== '.__('Transcrição de conversa terapêutica').' ===',
            __('Paciente').': '.$peerName,
            __('Profissional').': '.($conversation->professional?->name ?? '—'),
            __('Exportado em').': '.now()->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            __('Total de mensagens').': '.$messages->count(),
            str_repeat('—', 48),
            '',
        ];

        foreach ($messages as $message) {
            $channel = $message->channel === MessageChannel::Whatsapp ? 'WhatsApp' : 'PsiConecta';
            $sender = $message->sender?->name ?? __('Desconhecido');
            $timestamp = $message->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i');

            $lines[] = "[{$timestamp}] {$sender} ({$channel}):";
            $lines[] = $message->body;
            foreach ($message->attachments as $attachment) {
                $lines[] = __('Anexo').': '.$attachment->original_name.' ('.$attachment->humanSize().')';
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    public function downloadPdf(Conversation $conversation, User $actor): Response
    {
        $conversation->loadMissing(['professional', 'patientUser', 'patient']);
        $transcript = $this->buildTranscript($conversation);

        $filename = 'conversa-'.Str::slug($conversation->patient?->name ?? 'paciente').'-'.$conversation->id.'.pdf';

        return PdfFacade::loadView('conversations.pdf.transcript', [
            'conversation' => $conversation,
            'transcript' => $transcript,
            'actor' => $actor,
            'exportedAt' => now()->timezone(config('app.timezone')),
        ])->setPaper('a4')->download($filename);
    }
}
