<?php

namespace Tests\Feature;

use App\Models\ClinicalRecord;
use App\Models\Patient;
use App\Models\User;
use App\Support\ContactHasher;
use App\Support\CpfHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_professional_can_open_create_patient_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('patients.create'))
            ->assertOk()
            ->assertSee(__('Novo paciente'), false);
    }

    public function test_professional_can_create_and_list_patients(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->post(route('patients.store'), [
            'name' => 'Paciente Teste',
            'email' => 'paciente@example.com',
            'phone' => '11999990000',
            'birth_date' => '1990-01-15',
            'notes' => 'Observação inicial',
        ]);

        $response->assertRedirect();

        $patient = Patient::query()->where('name', 'Paciente Teste')->first();
        $this->assertNotNull($patient);
        $this->assertSame('paciente@example.com', $patient->email);
        $this->assertSame(ContactHasher::emailHash('paciente@example.com'), $patient->email_hash);
        $this->assertDatabaseHas('patients', [
            'professional_id' => $user->id,
            'name' => 'Paciente Teste',
            'email_hash' => ContactHasher::emailHash('paciente@example.com'),
        ]);

        $this->get(route('patients.index'))
            ->assertOk()
            ->assertSee('Paciente Teste');
    }

    public function test_store_rejects_invalid_cpf(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('patients.store'), [
            'name' => 'Com CPF ruim',
            'cpf' => '111.111.111-11',
        ])->assertSessionHasErrors('cpf');
    }

    public function test_store_accepts_valid_cpf_and_normalizes_address(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('patients.store'), [
            'name' => 'Com CPF',
            'cpf' => '529.982.247-25',
            'address_postal_code' => '01310-100',
            'address_street' => 'Av. Paulista',
            'address_number' => '1000',
            'address_city' => 'São Paulo',
            'address_state' => 'sp',
        ])->assertRedirect();

        $patient = Patient::query()->where('professional_id', $user->id)->where('name', 'Com CPF')->first();
        $this->assertNotNull($patient);
        $this->assertSame('52998224725', $patient->cpf);
        $this->assertSame(CpfHasher::hash('52998224725'), $patient->cpf_hash);
        $this->assertDatabaseHas('patients', [
            'professional_id' => $user->id,
            'name' => 'Com CPF',
            'cpf_hash' => CpfHasher::hash('52998224725'),
            'address_postal_code' => '01310100',
            'address_state' => 'SP',
        ]);
    }

    public function test_store_rejects_duplicate_cpf_same_professional(): void
    {
        $user = User::factory()->create();
        Patient::factory()->create([
            'professional_id' => $user->id,
            'cpf' => '52998224725',
        ]);

        $this->actingAs($user);

        $this->post(route('patients.store'), [
            'name' => 'Outro',
            'cpf' => '52998224725',
        ])->assertSessionHasErrors('cpf');
    }

    public function test_professional_cannot_view_other_professionals_patient(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $owner->id]);

        $this->actingAs($other);

        $this->get(route('patients.show', $patient))
            ->assertNotFound();
    }

    public function test_patient_show_displays_clinical_records_tab_and_history(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $user->id]);

        ClinicalRecord::query()->create([
            'patient_id' => $patient->id,
            'professional_id' => $user->id,
            'content' => 'Registro clínico de teste para histórico.',
        ]);

        $this->actingAs($user)
            ->get(route('patients.show', ['patient' => $patient, 'tab' => 'clinical-records']))
            ->assertOk()
            ->assertSee(__('Prontuário'), false)
            ->assertSee(__('Histórico do prontuário'), false)
            ->assertSee('Registro clínico de teste', false);
    }

    public function test_patient_show_displays_payments_tab_and_history(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $user->id]);

        \App\Models\Payment::factory()->create([
            'patient_id' => $patient->id,
            'amount' => 150.00,
            'status' => \App\Enums\PaymentStatus::Paid,
        ]);

        $this->actingAs($user)
            ->get(route('patients.show', ['patient' => $patient, 'tab' => 'payments']))
            ->assertOk()
            ->assertSee(__('Financeiro'), false)
            ->assertSee(__('Histórico de pagamentos'), false)
            ->assertSee('150,00', false);

        $this->actingAs($user)
            ->get(route('patients.show', $patient))
            ->assertOk()
            ->assertSee(__('Último pagamento'), false);
    }
}
