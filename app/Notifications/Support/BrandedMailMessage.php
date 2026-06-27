<?php

namespace App\Notifications\Support;

use Illuminate\Notifications\Messages\MailMessage;

class BrandedMailMessage extends MailMessage
{
    public static function create(): static
    {
        $appName = (string) config('app.name', 'PsiConecta');

        return (new static)
            ->salutation(__('Com os melhores cumprimentos,')."\n\n**{$appName}**");
    }
}
