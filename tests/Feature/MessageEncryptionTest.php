<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Message;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MessageEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_body_is_encrypted_at_rest(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
        ]);

        $this->actingAs($professional)->post(route('messages.store'), [
            'recipient_id' => $patient->id,
            'body' => 'Conteúdo confidencial da mensagem',
        ])->assertRedirect();

        $message = Message::query()->first();
        $this->assertNotNull($message);
        $this->assertSame('Conteúdo confidencial da mensagem', $message->body);

        $raw = DB::table('messages')->where('id', $message->id)->value('body');
        $this->assertNotSame('Conteúdo confidencial da mensagem', $raw);
    }

    public function test_sending_message_creates_audit_log(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
        ]);

        $this->actingAs($professional)->post(route('messages.store'), [
            'recipient_id' => $patient->id,
            'body' => 'Olá',
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'send',
            'entity' => 'messages',
        ]);
    }
}
