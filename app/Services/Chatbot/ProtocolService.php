<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Facades\DB;

class ProtocolService
{
    public function generate(): string
    {
        return DB::transaction(function () {
            $year = (int) now()->year;

            $row = DB::table('conversation_protocol_sequences')
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                DB::table('conversation_protocol_sequences')->insert([
                    'year' => $year,
                    'last_number' => 1,
                ]);

                $number = 1;
            } else {
                $number = (int) $row->last_number + 1;
                DB::table('conversation_protocol_sequences')
                    ->where('year', $year)
                    ->update(['last_number' => $number]);
            }

            return sprintf('PSC-%d-%07d', $year, $number);
        });
    }
}
