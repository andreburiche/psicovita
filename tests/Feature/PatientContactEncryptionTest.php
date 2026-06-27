<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use App\Support\ContactHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PatientContactEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_email_and_phone_are_encrypted_with_hashes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('patients.store'), [
            'name' => 'Contacto Seguro',
            'email' => 'PACIENTE@Example.COM',
            'phone' => '(11) 98765-4321',
        ])->assertRedirect();

        $patient = Patient::query()->where('name', 'Contacto Seguro')->first();
        $this->assertNotNull($patient);
        $this->assertSame('paciente@example.com', $patient->email);
        $this->assertSame('11987654321', $patient->phone);
        $this->assertSame(ContactHasher::emailHash('paciente@example.com'), $patient->email_hash);
        $this->assertSame(ContactHasher::phoneHash('11987654321'), $patient->phone_hash);

        $raw = DB::table('patients')->where('id', $patient->id)->first();
        $this->assertNotSame('paciente@example.com', $raw->email);
        $this->assertNotSame('11987654321', $raw->phone);
    }

    public function test_patient_search_by_email_uses_hash(): void
    {
        $user = User::factory()->create();
        Patient::factory()->create([
            'professional_id' => $user->id,
            'name' => 'Busca Email',
            'email' => 'busca@example.test',
        ]);

        $this->actingAs($user)
            ->get(route('patients.index', ['q' => 'busca@example.test']))
            ->assertOk()
            ->assertSee('Busca Email', false);
    }
}
