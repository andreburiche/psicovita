<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotIntent;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ChatbotMenuService
{
    /** @var list<string> */
    private const GUEST_MENU_SLUGS = [
        'schedule_appointment',
        'human_agent',
        'talk_to_professional',
        'professional_contact',
    ];

    /** @var list<string> */
    private const REGISTERED_MENU_SLUGS = [
        'schedule_appointment',
        'update_profile',
        'benefit_issue',
        'technical_support',
        'talk_to_professional',
        'human_agent',
    ];

    public function welcomeMessage(?User $user): string
    {
        if ($user === null) {
            $intro = __('Olá! Não encontramos seu cadastro no :app, mas você pode falar conosco por aqui.', [
                'app' => config('app.name'),
            ]);
        } else {
            $name = Str::before($user->name, ' ') ?: $user->name;
            $intro = __('Olá, :name! Sou o assistente do :app.', [
                'name' => $name,
                'app' => config('app.name'),
            ]);
        }

        return $intro."\n\n".$this->formatMenu($user);
    }

    public function formatMenu(?User $user): string
    {
        $lines = [__('Escolha uma opção (digite o número):')];

        foreach ($this->menuIntents($user) as $index => $intent) {
            $lines[] = sprintf('%d. %s', $index + 1, $intent->label);
        }

        if ($user === null) {
            $lines[] = '';
            $lines[] = __('Para criar conta, acesse: :url', ['url' => config('app.url')]);
        }

        return implode("\n", $lines);
    }

    /**
     * @return Collection<int, ChatbotIntent>
     */
    public function menuIntents(?User $user): Collection
    {
        $slugs = $user === null ? self::GUEST_MENU_SLUGS : self::REGISTERED_MENU_SLUGS;

        return ChatbotIntent::query()
            ->whereIn('slug', $slugs)
            ->where('is_active', true)
            ->whereHas('flow', fn ($q) => $q->where('is_active', true))
            ->with('targetQueue')
            ->get()
            ->sortBy(fn (ChatbotIntent $intent) => array_search($intent->slug, $slugs, true))
            ->values();
    }

    public function matchMenuSelection(string $body, ?User $user): ?ChatbotIntent
    {
        $intents = $this->menuIntents($user);
        if ($intents->isEmpty()) {
            return null;
        }

        $trimmed = trim($body);

        if (preg_match('/^(?:opcao|opção)?\s*(\d+)\.?$/iu', $trimmed, $matches) === 1) {
            return $intents->get(((int) $matches[1]) - 1);
        }

        if (ctype_digit($trimmed)) {
            return $intents->get(((int) $trimmed) - 1);
        }

        return null;
    }
}
