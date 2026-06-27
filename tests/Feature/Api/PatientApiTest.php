<?php

namespace Tests\Feature\Api;

use App\Enums\DataSubjectRequestType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\DeviceToken;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Services\ConversationService;
use App\Support\Api\PatientApiAbilities;
use App\Support\ContactHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PatientApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: User, 2: Patient}
     */
    private function patientWithFicha(string $email = 'paciente.api@example.test'): array
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patientUser = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
        $ficha = Patient::factory()->create([
            'professional_id' => $professional->id,
            'email' => $email,
            'email_hash' => ContactHasher::emailHash($email),
        ]);

        return [$patientUser, $professional, $ficha];
    }

    private function patientToken(User $user): string
    {
        return $user->createToken(
            'test',
            PatientApiAbilities::normalizeForToken(null),
        )->plainTextToken;
    }

    public function test_patient_can_login_and_access_home(): void
    {
        [$user] = $this->patientWithFicha();

        $login = $this->postJson('/api/v1/patient/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'test',
        ]);

        $login->assertOk()
            ->assertJsonPath('user.role', 'patient')
            ->assertJsonStructure(['token', 'user']);

        $token = $login->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/patient/home')
            ->assertOk()
            ->assertJsonStructure(['data' => ['user', 'therapist', 'pending_payments']]);
    }

    public function test_professional_cannot_use_patient_login(): void
    {
        User::factory()->create([
            'role' => UserRole::Professional,
            'email' => 'pro.api@example.test',
            'password' => Hash::make('password'),
        ]);

        $this->postJson('/api/v1/patient/auth/login', [
            'email' => 'pro.api@example.test',
            'password' => 'password',
        ])->assertStatus(403);
    }

    public function test_patient_can_view_and_update_profile(): void
    {
        [$user] = $this->patientWithFicha();
        $token = $this->patientToken($user);

        $this->withToken($token)
            ->getJson('/api/v1/patient/profile')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $this->withToken($token)
            ->patchJson('/api/v1/patient/profile', [
                'name' => 'Nome Atualizado API',
                'phone' => '11999998888',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nome Atualizado API');
    }

    public function test_patient_can_list_and_view_payments(): void
    {
        [$user, , $ficha] = $this->patientWithFicha();
        $token = $this->patientToken($user);

        $payment = Payment::factory()->create([
            'patient_id' => $ficha->id,
            'status' => PaymentStatus::Pending,
            'amount' => 150,
            'payment_method' => PaymentMethod::Pix,
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/patient/payments')
            ->assertOk()
            ->assertJsonStructure(['data']);

        $this->withToken($token)
            ->getJson('/api/v1/patient/payments/'.$payment->id)
            ->assertOk()
            ->assertJsonPath('data.id', $payment->id)
            ->assertJsonPath('data.status', PaymentStatus::Pending->value);
    }

    public function test_patient_can_initiate_payment_via_api(): void
    {
        [$user, , $ficha] = $this->patientWithFicha('pagar.api@example.test');
        $token = $this->patientToken($user);

        $payment = Payment::factory()->create([
            'patient_id' => $ficha->id,
            'status' => PaymentStatus::Pending,
            'amount' => 120,
            'payment_method' => null,
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/patient/payments/'.$payment->id.'/pay', [
                'payment_method' => PaymentMethod::Pix->value,
            ])
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'status', 'payment_method']]);

        $payment->refresh();
        $this->assertSame(PaymentMethod::Pix, $payment->payment_method);
    }

    public function test_patient_can_use_conversations_api(): void
    {
        [$user, $professional] = $this->patientWithFicha('conversa.api@example.test');
        $token = $this->patientToken($user);

        $conversation = app(ConversationService::class)->findOrCreateForUsers($professional, $user);
        app(ConversationService::class)->sendMessage($conversation, $professional, 'Olá via API');

        $this->withToken($token)
            ->getJson('/api/v1/patient/conversations')
            ->assertOk()
            ->assertJsonFragment(['id' => $conversation->id]);

        $this->withToken($token)
            ->getJson('/api/v1/patient/conversations/'.$conversation->id)
            ->assertOk()
            ->assertJsonPath('data.conversation.id', $conversation->id)
            ->assertJsonFragment(['body' => 'Olá via API']);

        $this->withToken($token)
            ->postJson('/api/v1/patient/conversations/'.$conversation->id.'/messages', [
                'body' => 'Resposta do paciente',
            ])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Resposta do paciente');

        $lastId = $conversation->messages()->max('id');

        $this->withToken($token)
            ->getJson('/api/v1/patient/conversations/'.$conversation->id.'/poll?after_id='.($lastId - 1))
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_patient_can_manage_lgpd_requests_via_api(): void
    {
        Mail::fake();
        [$user] = $this->patientWithFicha('lgpd.api@example.test');
        $token = $this->patientToken($user);

        $this->withToken($token)
            ->getJson('/api/v1/patient/lgpd/requests')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);

        $this->withToken($token)
            ->postJson('/api/v1/patient/lgpd/requests', [
                'type' => DataSubjectRequestType::Access->value,
                'details' => 'Solicitação via app mobile',
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', DataSubjectRequestType::Access->value);

        $this->assertDatabaseHas('data_subject_requests', [
            'user_id' => $user->id,
            'type' => DataSubjectRequestType::Access->value,
        ]);
    }

    public function test_patient_can_register_device_token(): void
    {
        [$user] = $this->patientWithFicha('push.api@example.test');
        $token = $this->patientToken($user);

        $this->withToken($token)
            ->postJson('/api/v1/patient/device-token', [
                'token' => 'fcm-token-test-abc',
                'platform' => 'android',
            ])
            ->assertOk();

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'fcm-token-test-abc',
            'platform' => 'android',
        ]);

        $this->withToken($token)
            ->deleteJson('/api/v1/patient/device-token', [
                'token' => 'fcm-token-test-abc',
            ])
            ->assertOk();

        $this->assertDatabaseMissing('device_tokens', [
            'user_id' => $user->id,
            'token' => 'fcm-token-test-abc',
        ]);
    }

    public function test_unauthenticated_patient_routes_return_401(): void
    {
        $this->getJson('/api/v1/patient/home')->assertUnauthorized();
        $this->getJson('/api/v1/patient/payments')->assertUnauthorized();
    }
}
