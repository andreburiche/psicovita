<?php

namespace Database\Seeders;

use App\Enums\PaymentStatus;
use App\Enums\TherapySessionStatus;
use App\Enums\UserRole;
use App\Models\ClinicalRecord;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\ScheduleBlock;
use App\Models\TherapySession;
use App\Models\User;
use App\Support\ContactHasher;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PsiConectaDemoSeeder extends Seeder
{
    public function run(): void
    {
        $professional = User::query()->updateOrCreate(
            ['email_hash' => ContactHasher::emailHash('profissional@psiconecta.test')],
            [
                'name' => 'Profissional Demo',
                'email' => 'profissional@psiconecta.test',
                'password' => 'password',
                'role' => UserRole::Professional,
                'email_verified_at' => now(),
                'professional_id' => null,
                'phone' => null,
                'whatsapp_notifications' => false,
            ]
        );

        User::query()->updateOrCreate(
            ['email_hash' => ContactHasher::emailHash('paciente@psiconecta.test')],
            [
                'name' => 'Paciente Demo',
                'email' => 'paciente@psiconecta.test',
                'password' => 'password',
                'role' => UserRole::Patient,
                'professional_id' => $professional->id,
                'email_verified_at' => now(),
                'phone' => '21999990000',
                'whatsapp_notifications' => false,
            ]
        );

        /*
         * Ficha clínica (tabela `patients`) = o que aparece em /patients.
         * Conta User "Paciente Demo" = login no portal; são registos diferentes até criarmos esta ligação.
         */
        Patient::query()->updateOrCreate(
            [
                'professional_id' => $professional->id,
                'email_hash' => ContactHasher::emailHash('paciente@psiconecta.test'),
            ],
            [
                'name' => 'Paciente Demo',
                'email' => 'paciente@psiconecta.test',
                'phone' => '21999990000',
                'birth_date' => null,
                'notes' => 'Ficha demo ligada ao utilizador paciente@psiconecta.test (portal do paciente).',
            ]
        );

        if (TherapySession::query()->where('professional_id', $professional->id)->exists()) {
            return;
        }

        $patients = Patient::factory()
            ->count(10)
            ->create(['professional_id' => $professional->id]);

        $dayOffset = 0;
        $times = ['09:00:00', '10:30:00', '15:00:00'];

        foreach ($patients as $patient) {
            for ($i = 0; $i < 3; $i++) {
                TherapySession::factory()->create([
                    'patient_id' => $patient->id,
                    'professional_id' => $professional->id,
                    'session_date' => Carbon::now()->addDays($dayOffset++)->format('Y-m-d'),
                    'session_time' => $times[$i % 3],
                    'status' => TherapySessionStatus::Scheduled,
                ]);
            }

            Payment::factory()->create([
                'patient_id' => $patient->id,
                'amount' => random_int(120, 280),
                'status' => PaymentStatus::Paid,
            ]);

            ClinicalRecord::query()->create([
                'patient_id' => $patient->id,
                'professional_id' => $professional->id,
                'content' => 'Registro demo (LGPD): evolução sintética para testes de relatório e prontuário.',
            ]);
        }

        ScheduleBlock::factory()->count(2)->create([
            'professional_id' => $professional->id,
            'block_date' => Carbon::now()->addWeek()->format('Y-m-d'),
            'start_time' => '12:00:00',
            'end_time' => '13:30:00',
            'reason' => 'Almoço / indisponibilidade',
        ]);
    }
}
