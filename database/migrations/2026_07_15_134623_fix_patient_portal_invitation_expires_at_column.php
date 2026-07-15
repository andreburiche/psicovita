<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MySQL marca o primeiro TIMESTAMP com ON UPDATE CURRENT_TIMESTAMP.
     * Ao gravar last_sent_at, expires_at era sobrescrito com NOW() e o convite
     * aparecia como «expirado» de imediato.
     */
    public function up(): void
    {
        if (! Schema::hasTable('patient_portal_invitations')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE patient_portal_invitations MODIFY expires_at DATETIME NOT NULL');
            DB::statement('ALTER TABLE patient_portal_invitations MODIFY accepted_at DATETIME NULL');
            DB::statement('ALTER TABLE patient_portal_invitations MODIFY last_sent_at DATETIME NULL');
        }

        $days = max(1, (int) config('patient_portal.invitation_expires_days', 7));

        DB::table('patient_portal_invitations')
            ->whereNull('accepted_at')
            ->update([
                'expires_at' => now()->addDays($days),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('patient_portal_invitations')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE patient_portal_invitations MODIFY expires_at TIMESTAMP NOT NULL');
            DB::statement('ALTER TABLE patient_portal_invitations MODIFY accepted_at TIMESTAMP NULL');
            DB::statement('ALTER TABLE patient_portal_invitations MODIFY last_sent_at TIMESTAMP NULL');
        }
    }
};
