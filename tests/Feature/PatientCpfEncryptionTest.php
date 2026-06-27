<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use App\Support\CpfHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PatientCpfEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cpf_is_encrypted_at_rest_and_searchable_by_hash(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('patients.store'), [
            'name' => 'Paciente CPF',
            'cpf' => '529.982.247-25',
        ])->assertRedirect();

        $patient = Patient::query()->where('name', 'Paciente CPF')->first();
        $this->assertNotNull($patient);

        $rawCpf = DB::table('patients')->where('id', $patient->id)->value('cpf');
        $this->assertNotSame('52998224725', $rawCpf);
        $this->assertSame('52998224725', $patient->cpf);
        $this->assertSame(CpfHasher::hash('52998224725'), $patient->cpf_hash);

        $this->get(route('patients.index', ['q' => '529.982.247-25']))
            ->assertOk()
            ->assertSee('Paciente CPF', false);
    }

    public function test_encrypt_cpf_command_migrates_plaintext(): void
    {
        $user = User::factory()->create();

        DB::table('patients')->insert([
            'professional_id' => $user->id,
            'name' => 'Legado',
            'cpf' => '52998224725',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('psiconecta:encrypt-cpf')->assertSuccessful();

        $patient = Patient::query()->where('name', 'Legado')->first();
        $this->assertNotNull($patient);
        $this->assertSame('52998224725', $patient->cpf);
        $this->assertSame(CpfHasher::hash('52998224725'), $patient->cpf_hash);

        $rawCpf = DB::table('patients')->where('id', $patient->id)->value('cpf');
        $this->assertNotSame('52998224725', $rawCpf);
    }
}
