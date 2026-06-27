<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use App\Support\ContactHasher;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ReassignPatientLoginEmailCommand extends Command
{
    protected $signature = 'patients:reassign-login-email
                            {email : E-mail normalizado do paciente}
                            {--dry-run : Apenas mostrar o que seria feito}';

    protected $description = 'Converte conta profissional errada para paciente quando a ficha clínica já usa o mesmo e-mail';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        if ($email === '') {
            $this->error('Informe um e-mail válido.');

            return self::FAILURE;
        }

        $hash = ContactHasher::emailHash($email);
        $user = User::query()->where('email_hash', $hash)->first();
        $patients = Patient::query()->where('email_hash', $hash)->orderBy('id')->get();

        if ($user === null) {
            $this->warn('Nenhuma conta de utilizador encontrada para este e-mail.');

            return self::SUCCESS;
        }

        if ($patients->isEmpty()) {
            $this->warn('Nenhuma ficha de paciente encontrada com este e-mail.');

            return self::SUCCESS;
        }

        if ($user->isPatient()) {
            $patient = $patients->first();
            $updates = [];
            if ($user->name !== $patient->name) {
                $updates['name'] = $patient->name;
            }
            if ((int) $user->professional_id !== (int) $patient->professional_id) {
                $updates['professional_id'] = $patient->professional_id;
            }

            if ($updates === []) {
                $this->info("Conta #{$user->id} já está correta como paciente ({$user->name}).");

                return self::SUCCESS;
            }

            if ($this->option('dry-run')) {
                $this->line('[dry-run] Actualizaria user #'.$user->id.': '.json_encode($updates));

                return self::SUCCESS;
            }

            $user->update($updates);
            $this->info("Conta #{$user->id} sincronizada com a ficha «{$patient->name}».");

            return self::SUCCESS;
        }

        if (! $user->isProfessional()) {
            $this->error("Conta #{$user->id} é {$user->role->value}; correcção manual necessária.");

            return self::FAILURE;
        }

        if ($patients->count() > 1) {
            $this->error('Existem várias fichas com este e-mail. Resolva manualmente antes de continuar.');
            foreach ($patients as $patient) {
                $this->line("- Patient #{$patient->id} {$patient->name} (prof {$patient->professional_id})");
            }

            return self::FAILURE;
        }

        $patient = $patients->first();

        $this->table(['Campo', 'Actual', 'Novo'], [
            ['role', $user->role->value, UserRole::Patient->value],
            ['name', $user->name, $patient->name],
            ['professional_id', (string) ($user->professional_id ?? '—'), (string) $patient->professional_id],
        ]);

        if ($this->option('dry-run')) {
            $this->info('[dry-run] Nenhuma alteração feita.');

            return self::SUCCESS;
        }

        $user->update([
            'role' => UserRole::Patient,
            'name' => $patient->name,
            'professional_id' => $patient->professional_id,
            'professional_function' => null,
            'clinic_owner_id' => null,
        ]);

        $this->info("Conta #{$user->id} convertida para paciente «{$patient->name}». Peça para iniciar sessão novamente.");

        return self::SUCCESS;
    }
}
