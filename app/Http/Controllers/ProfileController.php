<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Services\ClinicTeamService;
use App\Services\InstitutionLogoService;
use App\Services\SubscriptionService;
use App\Services\UserAvatarService;
use App\Support\UiAccentOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private readonly UserAvatarService $avatars,
        private readonly InstitutionLogoService $institutionLogos,
        private readonly SubscriptionService $subscriptions,
        private readonly ClinicTeamService $clinicTeams,
    ) {}

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user()->load(['professionalFiles', 'professionalSubscription.plan', 'clinicOwner']);

        return view('profile.edit', [
            'user' => $user,
            'subscriptionSummary' => $this->buildSubscriptionSummary($user),
            'canManageClinicTeam' => $this->clinicTeams->canManageTeam($user),
            'clinicTeamMembers' => $user->isClinicOwner() ? $this->clinicTeams->teamMembers($user) : collect(),
            'clinicPendingInvitations' => $user->isClinicOwner() ? $this->clinicTeams->pendingInvitations($user) : collect(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $fillable = [
            'name',
            'email',
            'phone',
            'crp_number',
            'professional_function',
            'professional_bio',
            'ui_accent',
        ];

        if ($user->isProfessional() && config('asaas.split_enabled')) {
            $fillable[] = 'asaas_wallet_id';
        }

        $user->fill($request->safe()->only($fillable));

        if ($user->isProfessional() && config('asaas.split_enabled') && $request->has('asaas_wallet_id') && $request->input('asaas_wallet_id') === '') {
            $user->asaas_wallet_id = null;
        }

        if ($request->boolean('remove_avatar')) {
            $this->avatars->remove($user);
        } elseif ($request->hasFile('avatar')) {
            $this->avatars->store($user, $request->file('avatar'));
        }

        if ($user->isClinicOwner()) {
            if ($request->boolean('remove_institution_logo')) {
                $this->institutionLogos->remove($user);
            } elseif ($request->hasFile('institution_logo')) {
                $this->institutionLogos->store($user, $request->file('institution_logo'));
            }
        }

        $user->avatar_style = $request->validatedAvatarStyle();
        $user->ui_accent = UiAccentOptions::resolve($request->input('ui_accent'));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        $rules = [
            'password' => ['required', 'current_password'],
        ];

        if ($user->isProfessional() && $user->patients()->exists()) {
            $rules['acknowledge_data_loss'] = ['accepted'];
        }

        $request->validateWithBag('userDeletion', $rules, [
            'acknowledge_data_loss.accepted' => __('Deve confirmar que compreende a eliminação permanente dos dados clínicos associados à sua conta.'),
        ]);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * @return array{subscription: \App\Models\ProfessionalSubscription|null, is_active: bool, expires_at: \Illuminate\Support\Carbon|null}
     */
    private function buildSubscriptionSummary(User $user): array
    {
        $subscription = $this->subscriptions->activeSubscription($user);

        $expiresAt = null;
        if ($subscription !== null) {
            $expiresAt = $subscription->status === \App\Enums\SubscriptionStatus::Trialing
                ? $subscription->trial_ends_at
                : $subscription->ends_at;
        }

        return [
            'subscription' => $subscription,
            'is_active' => $this->subscriptions->isActive($user),
            'expires_at' => $expiresAt,
        ];
    }
}
