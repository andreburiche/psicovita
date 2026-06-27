<?php

namespace App\Console\Commands;

use App\Support\ContactHasher;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EncryptUserEmailsCommand extends Command
{
    protected $signature = 'psiconecta:encrypt-user-emails
                            {--dry-run : Apenas listar registros elegíveis}';

    protected $description = 'Criptografa e-mail e telefone de utilizadores e garante hashes de busca (idempotente).';

    public function handle(): int
    {
        $updated = 0;
        $skipped = 0;

        DB::table('users')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use (&$updated, &$skipped) {
                foreach ($rows as $row) {
                    $payload = [];
                    $changed = false;
                    $labels = [];

                    if ($row->email !== null && $row->email !== '') {
                        $resolved = $this->resolveEmail((string) $row->email);
                        if ($resolved !== null) {
                            if (! $resolved['encrypted']) {
                                $payload['email'] = Crypt::encryptString($resolved['normalized']);
                                $changed = true;
                            }

                            $expectedHash = ContactHasher::emailHash($resolved['normalized']);
                            if ($row->email_hash !== $expectedHash) {
                                $payload['email_hash'] = $expectedHash;
                                $changed = true;
                            }

                            if ($changed) {
                                $labels[] = $resolved['normalized'];
                            }
                        }
                    }

                    if ($row->phone !== null && $row->phone !== '') {
                        $phoneResult = $this->resolvePhone((string) $row->phone);
                        if ($phoneResult !== null) {
                            if (! $phoneResult['encrypted']) {
                                $payload['phone'] = Crypt::encryptString($phoneResult['digits']);
                                $changed = true;
                            }

                            $expectedPhoneHash = ContactHasher::phoneHash($phoneResult['digits']);
                            if ($row->phone_hash !== $expectedPhoneHash) {
                                $payload['phone_hash'] = $expectedPhoneHash;
                                $changed = true;
                            }

                            if ($changed) {
                                $labels[] = $phoneResult['digits'];
                            }
                        }
                    }

                    if (! $changed) {
                        $skipped++;

                        continue;
                    }

                    if ($this->option('dry-run')) {
                        $this->line('User #'.$row->id.': '.implode(', ', array_unique($labels)));
                        $updated++;

                        continue;
                    }

                    DB::table('users')->where('id', $row->id)->update($payload);
                    $updated++;
                }
            });

        $this->info($this->option('dry-run')
            ? "Elegíveis para atualização: {$updated} (ignorados: {$skipped})"
            : "Atualizados: {$updated} (ignorados: {$skipped})");

        return self::SUCCESS;
    }

    /**
     * @return array{normalized: string, encrypted: bool}|null
     */
    private function resolveEmail(string $value): ?array
    {
        try {
            $decrypted = Crypt::decryptString($value);

            return [
                'normalized' => Str::lower(trim($decrypted)),
                'encrypted' => true,
            ];
        } catch (DecryptException) {
            if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return null;
            }

            return [
                'normalized' => Str::lower(trim($value)),
                'encrypted' => false,
            ];
        }
    }

    /**
     * @return array{digits: string, encrypted: bool}|null
     */
    private function resolvePhone(string $value): ?array
    {
        try {
            $decrypted = Crypt::decryptString($value);
            $digits = only_digits($decrypted);

            return strlen($digits) >= 10
                ? ['digits' => $digits, 'encrypted' => true]
                : null;
        } catch (DecryptException) {
            $digits = only_digits($value);

            return strlen($digits) >= 10
                ? ['digits' => $digits, 'encrypted' => false]
                : null;
        }
    }
}
