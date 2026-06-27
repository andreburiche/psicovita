<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppGatewayInterface;
use App\Models\Conversation;
use App\Models\MessageAttachment;
use InvalidArgumentException;

class WhatsAppGatewayFactory
{
    public function make(?string $driver = null): WhatsAppGatewayInterface
    {
        $driver ??= (string) config('psiconecta.whatsapp.driver', 'meta');

        return match ($driver) {
            'meta' => app(MetaWhatsAppGateway::class),
            'evolution' => app(EvolutionWhatsAppGateway::class),
            default => throw new InvalidArgumentException("WhatsApp driver [{$driver}] não suportado."),
        };
    }

    public function driverLabel(?string $driver = null): string
    {
        $driver ??= (string) config('psiconecta.whatsapp.driver', 'meta');

        return match ($driver) {
            'evolution' => 'Evolution API',
            default => 'WhatsApp Cloud API',
        };
    }
}
