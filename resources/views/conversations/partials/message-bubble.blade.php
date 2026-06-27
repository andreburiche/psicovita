@props(['message', 'user', 'conversation'])

@php
    use App\Enums\MessageChannel;

    $mine = $message->isFrom($user);
@endphp

<div @class(['flex', 'justify-end' => $mine, 'justify-start' => ! $mine]) data-message-id="{{ $message->id }}">
    <div @class([
        'max-w-[85%] rounded-2xl px-4 py-3 text-sm shadow-sm',
        'rounded-br-md bg-gradient-to-br from-violet-600 to-indigo-600 text-white' => $mine,
        'rounded-bl-md border border-slate-200 bg-white text-slate-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100' => ! $mine,
    ])>
        @if ($message->channel === MessageChannel::Whatsapp || ($mine && $message->external_id))
            <span class="mb-1 inline-flex items-center gap-1 rounded-md bg-black/10 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide">{{ __('WhatsApp') }}</span>
        @endif
        <p class="whitespace-pre-wrap break-words leading-relaxed">{{ $message->body }}</p>

        @if ($message->attachments->isNotEmpty())
            <ul class="mt-2 space-y-1.5" role="list">
                @foreach ($message->attachments as $attachment)
                    <li>
                        <a
                            href="{{ route('conversations.attachments.download', [$conversation, $attachment]) }}"
                            @class([
                                'inline-flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-semibold transition',
                                'bg-white/15 text-white hover:bg-white/25' => $mine,
                                'bg-slate-100 text-slate-800 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-100' => ! $mine,
                            ])
                        >
                            <x-ui.icon name="paper-clip" class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">{{ $attachment->original_name }}</span>
                            <span class="opacity-70">({{ $attachment->humanSize() }})</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        <p @class(['mt-1.5 text-[10px] font-medium', 'text-violet-100/80' => $mine, 'text-slate-400' => ! $mine])>
            {{ $message->created_at->format('d/m/Y H:i') }}
            @if ($mine && $message->read_at)
                · {{ __('Lida') }}
            @endif
            @if ($mine && $message->external_id)
                · {{ __('WhatsApp ✓') }}
            @endif
        </p>
    </div>
</div>
