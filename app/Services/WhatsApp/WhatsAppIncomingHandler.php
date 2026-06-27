<?php

namespace App\Services\WhatsApp;

use App\Enums\MessageChannel;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\SupportMessage;
use App\Services\Chatbot\SupportWhatsAppHandler;
use App\Support\ContactHasher;
use Illuminate\Support\Facades\Log;

class WhatsAppIncomingHandler
{
    /**
     * @param  array{contents: string, mime_type: string, original_name: string}|null  $attachment
     */
    public function store(
        string $fromDigits,
        string $body,
        string $externalId,
        string $type = 'text',
        ?array $attachment = null,
    ): bool {
        if ($fromDigits === '' || $externalId === '') {
            return false;
        }

        if (trim($body) === '' && $attachment === null) {
            return false;
        }

        if (Message::query()->where('external_id', $externalId)->exists()
            || SupportMessage::query()->where('external_id', $externalId)->exists()) {
            return false;
        }

        $phoneHashes = collect(self::phoneDigitVariants($fromDigits))
            ->map(fn (string $digits) => ContactHasher::phoneHash($digits))
            ->unique()
            ->values()
            ->all();

        $conversation = Conversation::query()
            ->where('whatsapp_enabled', true)
            ->whereIn('whatsapp_phone_hash', $phoneHashes)
            ->with('patientUser')
            ->first();

        if ($conversation !== null && $conversation->patientUser !== null) {
            $message = app(\App\Services\ConversationService::class)->sendMessage(
                $conversation,
                $conversation->patientUser,
                $body !== '' ? $body : __('📎 Anexo'),
                MessageChannel::Whatsapp,
                externalId: $externalId,
            );

            if ($attachment !== null) {
                app(\App\Services\MessageAttachmentService::class)->storeFromBinary(
                    $message,
                    $attachment['contents'],
                    $attachment['mime_type'],
                    $attachment['original_name'],
                );
            }

            return true;
        }

        if ($body !== '' && app(SupportWhatsAppHandler::class)->handle($fromDigits, $body, $externalId, $type)) {
            return true;
        }

        Log::info('WhatsApp message without matching conversation', [
            'from' => $fromDigits,
            'type' => $type,
        ]);

        return false;
    }

    public static function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?: '';

        if ($digits === '') {
            return '';
        }

        $defaultCode = preg_replace('/\D+/', '', (string) config('psiconecta.whatsapp.default_calling_code', '')) ?: '';

        if ($defaultCode !== '' && ! str_starts_with($digits, $defaultCode)) {
            $digits = $defaultCode.ltrim($digits, '0');
        }

        return $digits;
    }

    /**
     * @return list<string>
     */
    public static function phoneDigitVariants(string $value): array
    {
        $normalized = self::normalizePhone($value);
        if ($normalized === '') {
            return [];
        }

        $variants = [$normalized];

        $defaultCode = preg_replace('/\D+/', '', (string) config('psiconecta.whatsapp.default_calling_code', '')) ?: '';
        if ($defaultCode !== '' && str_starts_with($normalized, $defaultCode)) {
            $withoutCode = substr($normalized, strlen($defaultCode));
            if ($withoutCode !== '') {
                $variants[] = $withoutCode;
                $variants[] = ltrim($withoutCode, '0');
            }
        }

        return array_values(array_unique(array_filter($variants)));
    }
}
