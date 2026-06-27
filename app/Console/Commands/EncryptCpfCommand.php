<?php

namespace App\Console\Commands;

use App\Support\CpfHasher;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EncryptCpfCommand extends Command
{
    protected $signature = 'psiconecta:encrypt-cpf {--dry-run : Apenas listar registros elegíveis}';

    protected $description = 'Criptografa CPFs em texto plano e preenche cpf_hash (idempotente).';

    public function handle(): int
    {
        $updated = 0;
        $skipped = 0;

        DB::table('patients')
            ->whereNotNull('cpf')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use (&$updated, &$skipped) {
                foreach ($rows as $row) {
                    $raw = (string) $row->cpf;
                    $digits = $this->resolveDigits($raw);

                    if ($digits === null) {
                        $skipped++;

                        continue;
                    }

                    $alreadyEncrypted = $this->isEncrypted($raw);
                    $hasHash = filled($row->cpf_hash);

                    if ($alreadyEncrypted && $hasHash) {
                        $skipped++;

                        continue;
                    }

                    if ($this->option('dry-run')) {
                        $this->line("Paciente #{$row->id}: ".($alreadyEncrypted ? 'hash' : 'encrypt+hash'));
                        $updated++;

                        continue;
                    }

                    $payload = ['cpf_hash' => CpfHasher::hash($digits), 'updated_at' => now()];
                    if (! $alreadyEncrypted) {
                        $payload['cpf'] = Crypt::encryptString($digits);
                    }

                    DB::table('patients')->where('id', $row->id)->update($payload);
                    $updated++;
                }
            }, 'id');

        $verb = $this->option('dry-run') ? __('seriam atualizados') : __('atualizados');

        $this->info(__(':count registro(s) :verb; :skipped ignorado(s).', [
            'count' => $updated,
            'verb' => $verb,
            'skipped' => $skipped,
        ]));

        return self::SUCCESS;
    }

    private function isEncrypted(string $raw): bool
    {
        try {
            Crypt::decryptString($raw);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }

    private function resolveDigits(string $raw): ?string
    {
        if ($this->isEncrypted($raw)) {
            try {
                $plain = Crypt::decryptString($raw);
                $digits = only_digits($plain);

                return strlen($digits) === 11 ? $digits : null;
            } catch (DecryptException) {
                return null;
            }
        }

        $digits = only_digits($raw);

        return strlen($digits) === 11 && ctype_digit($digits) ? $digits : null;
    }
}
