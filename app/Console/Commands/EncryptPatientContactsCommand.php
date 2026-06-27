<?php

namespace App\Console\Commands;

use App\Support\ContactHasher;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EncryptPatientContactsCommand extends Command
{
    protected $signature = 'psiconecta:encrypt-patient-contacts {--dry-run : Apenas listar registros elegíveis}';

    protected $description = 'Criptografa e-mail/telefone de pacientes e preenche hashes de busca (idempotente).';

    public function handle(): int
    {
        $updated = 0;
        $skipped = 0;

        DB::table('patients')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use (&$updated, &$skipped) {
                foreach ($rows as $row) {
                    $payload = [];
                    $changed = false;

                    if ($row->email !== null && $row->email !== '') {
                        $emailResult = $this->resolveEmail((string) $row->email);
                        if ($emailResult !== null) {
                            if (! $emailResult['encrypted']) {
                                $payload['email'] = Crypt::encryptString($emailResult['normalized']);
                                $changed = true;
                            }
                            if ($row->email_hash !== ContactHasher::emailHash($emailResult['normalized'])) {
                                $payload['email_hash'] = ContactHasher::emailHash($emailResult['normalized']);
                                $changed = true;
                            }
                        }
                    } elseif ($row->email_hash !== null) {
                        $payload['email_hash'] = null;
                        $changed = true;
                    }

                    if ($row->phone !== null && $row->phone !== '') {
                        $phoneResult = $this->resolvePhone((string) $row->phone);
                        if ($phoneResult !== null) {
                            if (! $phoneResult['encrypted']) {
                                $payload['phone'] = Crypt::encryptString($phoneResult['digits']);
                                $changed = true;
                            }
                            if ($row->phone_hash !== ContactHasher::phoneHash($phoneResult['digits'])) {
                                $payload['phone_hash'] = ContactHasher::phoneHash($phoneResult['digits']);
                                $changed = true;
                            }
                        }
                    } elseif ($row->phone_hash !== null) {
                        $payload['phone_hash'] = null;
                        $changed = true;
                    }

                    if (! $changed) {
                        $skipped++;

                        continue;
                    }

                    if ($this->option('dry-run')) {
                        $this->line("Paciente #{$row->id}: atualizar contactos");
                        $updated++;

                        continue;
                    }

                    $payload['updated_at'] = now();
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

    /**
     * @return array{normalized: string, encrypted: bool}|null
     */
    private function resolveEmail(string $raw): ?array
    {
        if ($this->isEncrypted($raw)) {
            try {
                $plain = Crypt::decryptString($raw);
                $normalized = Str::lower(trim($plain));

                return $normalized !== '' ? ['normalized' => $normalized, 'encrypted' => true] : null;
            } catch (DecryptException) {
                return null;
            }
        }

        $normalized = Str::lower(trim($raw));

        return $normalized !== '' ? ['normalized' => $normalized, 'encrypted' => false] : null;
    }

    /**
     * @return array{digits: string, encrypted: bool}|null
     */
    private function resolvePhone(string $raw): ?array
    {
        if ($this->isEncrypted($raw)) {
            try {
                $digits = only_digits(Crypt::decryptString($raw));

                return strlen($digits) >= 10 ? ['digits' => $digits, 'encrypted' => true] : null;
            } catch (DecryptException) {
                return null;
            }
        }

        $digits = only_digits($raw);

        return strlen($digits) >= 10 ? ['digits' => $digits, 'encrypted' => false] : null;
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
}
