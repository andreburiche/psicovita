<?php

namespace Tests\Feature;

use App\Enums\SubscriptionPlanSlug;
use App\Enums\SubscriptionStatus;
use App\Models\ClinicInvitation;
use App\Models\Patient;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\ClinicTeamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ClinicTeamTest extends TestCase
{
    use RefreshDatabase;

    private function activateClinicaPlan(User $owner): void
    {
        $plan = SubscriptionPlan::query()->where('slug', SubscriptionPlanSlug::Clinica)->firstOrFail();
        $owner->professionalSubscription->update([
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'trial_ends_at' => null,
            'ends_at' => now()->addYear(),
        ]);
    }

    public function test_owner_with_clinica_plan_can_invite_team_member(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['email' => 'owner@clinic.test']);
        $this->activateClinicaPlan($owner);

        $this->actingAs($owner);

        $this->post(route('clinic.invitations.store'), [
            'email' => 'member@clinic.test',
        ])->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('clinic_invitations', [
            'clinic_owner_id' => $owner->id,
            'email' => 'member@clinic.test',
        ]);
    }

    public function test_team_member_can_access_owner_patients(): void
    {
        $owner = User::factory()->create();
        $this->activateClinicaPlan($owner);

        $member = User::factory()->create([
            'email' => 'member@clinic.test',
            'clinic_owner_id' => $owner->id,
        ]);

        $patient = Patient::factory()->create([
            'professional_id' => $owner->id,
            'name' => 'Paciente Equipa',
        ]);

        $this->actingAs($member);

        $this->get(route('patients.show', $patient))
            ->assertOk()
            ->assertSee('Paciente Equipa', false);
    }

    public function test_accept_invitation_links_member_to_clinic(): void
    {
        $owner = User::factory()->create(['email' => 'owner@clinic.test']);
        $this->activateClinicaPlan($owner);

        $member = User::factory()->create(['email' => 'member@clinic.test']);

        $invitation = ClinicInvitation::query()->create([
            'clinic_owner_id' => $owner->id,
            'email' => 'member@clinic.test',
            'email_hash' => \App\Support\ContactHasher::emailHash('member@clinic.test'),
            'token' => 'testtoken123',
            'expires_at' => now()->addWeek(),
        ]);

        $this->actingAs($member);

        app(ClinicTeamService::class)->acceptInvitation($invitation, $member);

        $this->assertSame($owner->id, $member->fresh()->clinic_owner_id);
    }

    public function test_team_member_cannot_access_subscription_checkout(): void
    {
        $owner = User::factory()->create();
        $this->activateClinicaPlan($owner);

        $member = User::factory()->create([
            'clinic_owner_id' => $owner->id,
        ]);

        $this->actingAs($member);

        $this->get(route('subscription.checkout'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_downgrade_from_clinica_to_premium_releases_team_members(): void
    {
        $owner = User::factory()->create();
        $this->activateClinicaPlan($owner);

        $member = User::factory()->create([
            'email' => 'member@clinic.test',
            'clinic_owner_id' => $owner->id,
        ]);

        ClinicInvitation::query()->create([
            'clinic_owner_id' => $owner->id,
            'email' => 'pending@clinic.test',
            'email_hash' => \App\Support\ContactHasher::emailHash('pending@clinic.test'),
            'token' => 'pendingtoken01',
            'expires_at' => now()->addWeek(),
        ]);

        $premium = SubscriptionPlan::query()->where('slug', SubscriptionPlanSlug::Premium)->firstOrFail();

        $this->actingAs($owner);

        $this->post(route('subscription.checkout.store'), [
            'subscription_plan_id' => $premium->id,
            'payment_method' => \App\Enums\PaymentMethod::Pix->value,
            'billing_cycle' => \App\Enums\BillingCycle::Monthly->value,
        ])->assertRedirect(route('subscription.checkout'));

        $this->assertNull($member->fresh()->clinic_owner_id);
        $this->assertDatabaseMissing('clinic_invitations', [
            'clinic_owner_id' => $owner->id,
            'email' => 'pending@clinic.test',
        ]);
    }

    public function test_team_member_loses_access_when_owner_downgrades(): void
    {
        $owner = User::factory()->create();
        $this->activateClinicaPlan($owner);

        $member = User::factory()->create([
            'clinic_owner_id' => $owner->id,
        ]);

        $patient = Patient::factory()->create([
            'professional_id' => $owner->id,
            'name' => 'Paciente Bloqueado',
        ]);

        $premium = SubscriptionPlan::query()->where('slug', SubscriptionPlanSlug::Premium)->firstOrFail();
        $owner->professionalSubscription->update([
            'subscription_plan_id' => $premium->id,
            'status' => SubscriptionStatus::Active,
        ]);

        $this->actingAs($member);

        $this->get(route('patients.show', $patient))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status');

        $this->assertNull($member->fresh()->clinic_owner_id);
    }
}
