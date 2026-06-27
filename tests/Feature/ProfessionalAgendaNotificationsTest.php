<?php

namespace Tests\Feature;

use App\Enums\TherapySessionStatus;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\TherapySession;
use App\Models\User;
use App\Notifications\ProfessionalDailyAgendaNotification;
use App\Notifications\ProfessionalUpcomingSessionReminderNotification;
use App\Services\ProfessionalAgendaNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ProfessionalAgendaNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_agenda_command_notifies_professional_with_today_sessions_once(): void
    {
        Carbon::setTestNow('2026-06-04 07:00:00');

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        TherapySession::factory()->create([
            'patient_id' => $patient->id,
            'professional_id' => $professional->id,
            'session_date' => '2026-06-04',
            'session_time' => '09:00:00',
            'status' => TherapySessionStatus::Scheduled,
        ]);

        TherapySession::factory()->create([
            'patient_id' => $patient->id,
            'professional_id' => $professional->id,
            'session_date' => '2026-06-04',
            'session_time' => '11:00:00',
            'status' => TherapySessionStatus::Scheduled,
        ]);

        $this->assertSame(0, Artisan::call('psiconecta:professional-daily-agenda'));

        $this->assertDatabaseCount('notifications', 1);
        $notification = $professional->fresh()->notifications->first();
        $this->assertSame(ProfessionalDailyAgendaNotification::class, $notification->type);
        $this->assertSame('2026-06-04', data_get($notification->data, 'agenda_date'));
        $this->assertSame(2, (int) data_get($notification->data, 'sessions_count'));

        Artisan::call('psiconecta:professional-daily-agenda');
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_daily_agenda_skips_professionals_without_scheduled_sessions_today(): void
    {
        Carbon::setTestNow('2026-06-04 07:00:00');

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        TherapySession::factory()->create([
            'patient_id' => $patient->id,
            'professional_id' => $professional->id,
            'session_date' => '2026-06-05',
            'session_time' => '09:00:00',
            'status' => TherapySessionStatus::Scheduled,
        ]);

        Artisan::call('psiconecta:professional-daily-agenda');

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_upcoming_session_reminder_sent_ten_minutes_before(): void
    {
        Carbon::setTestNow('2026-06-04 09:50:00');

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create([
            'professional_id' => $professional->id,
            'name' => 'Ana Demo',
        ]);

        $session = TherapySession::factory()->create([
            'patient_id' => $patient->id,
            'professional_id' => $professional->id,
            'session_date' => '2026-06-04',
            'session_time' => '10:00:00',
            'status' => TherapySessionStatus::Scheduled,
        ]);

        $this->assertSame(0, Artisan::call('psiconecta:professional-session-upcoming'));

        $notification = $professional->fresh()->notifications->first();
        $this->assertSame(ProfessionalUpcomingSessionReminderNotification::class, $notification->type);
        $this->assertSame($session->id, (int) data_get($notification->data, 'therapy_session_id'));
        $this->assertStringContainsString('Ana Demo', (string) data_get($notification->data, 'message'));

        Artisan::call('psiconecta:professional-session-upcoming');
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_upcoming_session_reminder_not_sent_outside_window(): void
    {
        Carbon::setTestNow('2026-06-04 09:40:00');

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        TherapySession::factory()->create([
            'patient_id' => $patient->id,
            'professional_id' => $professional->id,
            'session_date' => '2026-06-04',
            'session_time' => '10:00:00',
            'status' => TherapySessionStatus::Scheduled,
        ]);

        app(ProfessionalAgendaNotificationService::class)->sendUpcomingReminders();

        $this->assertDatabaseCount('notifications', 0);
    }
}
