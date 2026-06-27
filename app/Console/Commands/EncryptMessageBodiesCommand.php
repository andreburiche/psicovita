<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EncryptMessageBodiesCommand extends Command
{
    protected $signature = 'psiconecta:encrypt-message-bodies {--dry-run : Apenas listar registros elegíveis}';

    protected $description = 'Criptografa o corpo das mensagens internas (idempotente).';

    public function handle(): int
    {
        $updated = 0;
        $skipped = 0;

        DB::table('messages')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use (&$updated, &$skipped) {
                foreach ($rows as $row) {
                    if ($row->body === null || $row->body === '') {
                        $skipped++;

                        continue;
                    }

                    try {
                        Crypt::decryptString((string) $row->body);
                        $skipped++;

                        continue;
                    } catch (DecryptException) {
                        // plaintext — encrypt below
                    }

                    if ($this->option('dry-run')) {
                        $this->line('Message #'.$row->id);
                        $updated++;

                        continue;
                    }

                    DB::table('messages')->where('id', $row->id)->update([
                        'body' => Crypt::encryptString((string) $row->body),
                    ]);
                    $updated++;
                }
            });

        $this->info($this->option('dry-run')
            ? "Elegíveis para atualização: {$updated} (ignorados: {$skipped})"
            : "Atualizados: {$updated} (ignorados: {$skipped})");

        return self::SUCCESS;
    }
}
