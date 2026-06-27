@if ($canManageClinicTeam ?? false)
    <section class="rounded-2xl border border-indigo-200/80 bg-white p-6 shadow-lg shadow-indigo-900/5 ring-1 ring-indigo-100/60 dark:border-slate-700 dark:bg-slate-900/90 dark:ring-indigo-900/30">
        <div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Equipa clínica') }}</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                {{ __('Convide colegas para acederem ao mesmo consultório (plano Clínica).') }}
            </p>
        </div>

        @error('clinic_team')
            <x-flash-alert type="error" :message="$message" class="mt-4" />
        @enderror

        @if (($clinicTeamMembers ?? collect())->isNotEmpty())
            <ul class="mt-5 space-y-2">
                @foreach ($clinicTeamMembers as $member)
                    <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200/80 bg-slate-50/80 px-4 py-3 dark:border-slate-600 dark:bg-slate-800/50">
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-white">{{ $member->name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $member->email }}</p>
                        </div>
                        <form method="POST" action="{{ route('clinic.members.destroy', $member) }}" onsubmit="return confirm(@js(__('Remover este membro da equipa?')));">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-500 dark:text-rose-400">{{ __('Remover') }}</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif

        @if (($clinicPendingInvitations ?? collect())->isNotEmpty())
            <div class="mt-4">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Convites pendentes') }}</p>
                <ul class="mt-2 space-y-1 text-sm text-slate-600 dark:text-slate-300">
                    @foreach ($clinicPendingInvitations as $invite)
                        <li>{{ $invite->email }} · {{ __('expira') }} {{ $invite->expires_at->format('d/m/Y') }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('clinic.invitations.store') }}" class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
            @csrf
            <div class="flex-1">
                <x-input-label for="clinic_invite_email" :value="__('E-mail do colega')" class="text-slate-700 dark:text-slate-200" />
                <input id="clinic_invite_email" name="email" type="email" required class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900" placeholder="colega@example.com" />
            </div>
            <button type="submit" class="inline-flex items-center rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md hover:from-indigo-500 hover:to-violet-500">
                {{ __('Enviar convite') }}
            </button>
        </form>
    </section>
@elseif ($user->isClinicTeamMember())
    <section class="rounded-2xl border border-sky-200/80 bg-sky-50/50 p-5 dark:border-sky-900/40 dark:bg-sky-950/20">
        <p class="text-sm text-sky-900 dark:text-sky-200">
            {{ __('Faz parte da equipa de') }} <span class="font-semibold">{{ $user->clinicOwner?->name }}</span>.
            {{ __('A assinatura e pagamentos são geridos pelo titular da clínica.') }}
        </p>
    </section>
@endif
