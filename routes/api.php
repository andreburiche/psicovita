<?php

use App\Http\Controllers\Api\Integrations\EvolutionWebhookController;
use App\Http\Controllers\Api\Integrations\WhatsAppWebhookController;
use App\Http\Controllers\Api\V1\Patient\AuthController as PatientAuthController;
use App\Http\Controllers\Api\V1\Patient\ConversationController as PatientConversationController;
use App\Http\Controllers\Api\V1\Patient\DeviceTokenController;
use App\Http\Controllers\Api\V1\Patient\HomeController as PatientHomeController;
use App\Http\Controllers\Api\V1\Patient\LgpdController as PatientLgpdController;
use App\Http\Controllers\Api\V1\Patient\NotificationController as PatientNotificationController;
use App\Http\Controllers\Api\V1\Patient\PaymentController as PatientPaymentController;
use App\Http\Controllers\Api\V1\Patient\ProfileController as PatientProfileController;
use App\Http\Controllers\Api\V1\Patient\SessionController as PatientSessionController;
use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\ClinicalRecordController;
use App\Http\Controllers\Api\V1\OpenApiController;
use App\Http\Controllers\Api\V1\PatientController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\SummaryController;
use App\Http\Controllers\Api\V1\TherapySessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/health', fn () => response()->json([
        'service' => 'PsiConecta',
        'status' => 'ok',
    ]));

    Route::get('/openapi.json', OpenApiController::class)
        ->middleware('throttle:60,1')
        ->name('openapi');

    Route::prefix('integrations/whatsapp')->name('integrations.whatsapp.')->group(function () {
        Route::get('webhook', [WhatsAppWebhookController::class, 'verify'])
            ->middleware('throttle:30,1')
            ->name('webhook.verify');
        Route::post('webhook', [WhatsAppWebhookController::class, 'handle'])
            ->middleware('throttle:60,1')
            ->name('webhook');
    });

    Route::post('integrations/evolution/webhook', [EvolutionWebhookController::class, 'handle'])
        ->middleware('throttle:60,1')
        ->name('integrations.evolution.webhook');

    Route::post('/auth/login', [AuthTokenController::class, 'login'])
        ->middleware('throttle:12,1')
        ->name('auth.login');

    Route::prefix('patient/auth')->name('patient.auth.')->middleware('throttle:12,1')->group(function () {
        Route::post('login', [PatientAuthController::class, 'login'])->name('login');
        Route::post('register', [PatientAuthController::class, 'register'])->name('register');
        Route::post('social/google', [PatientAuthController::class, 'socialGoogle'])->name('social.google');
        Route::post('social/facebook', [PatientAuthController::class, 'socialFacebook'])->name('social.facebook');
        Route::post('social/complete', [PatientAuthController::class, 'completeSocialRegistration'])->name('social.complete');
    });

    $readAbilities = 'ability:*,api:read,api:write';
    $patientRead = 'ability:patient:*,patient:read,patient:write';

    Route::middleware(['auth:sanctum', 'patient.api', 'throttle:120,1', $patientRead])->prefix('patient')->name('patient.')->group(function () {
        Route::post('auth/logout', [PatientAuthController::class, 'logout'])->name('auth.logout');

        Route::get('home', PatientHomeController::class)->name('home');
        Route::get('profile', [PatientProfileController::class, 'show'])->name('profile.show');
        Route::patch('profile', [PatientProfileController::class, 'update'])->name('profile.update');

        Route::get('payments', [PatientPaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/{payment}', [PatientPaymentController::class, 'show'])->name('payments.show');
        Route::post('payments/{payment}/pay', [PatientPaymentController::class, 'pay'])->name('payments.pay');

        Route::get('sessions', [PatientSessionController::class, 'index'])->name('sessions.index');
        Route::post('sessions/{therapy_session}/join', [PatientSessionController::class, 'join'])->name('sessions.join');

        Route::get('conversations', [PatientConversationController::class, 'index'])->name('conversations.index');
        Route::get('conversations/{conversation}', [PatientConversationController::class, 'show'])->name('conversations.show');
        Route::post('conversations/{conversation}/messages', [PatientConversationController::class, 'storeMessage'])->name('conversations.messages.store');
        Route::get('conversations/{conversation}/poll', [PatientConversationController::class, 'poll'])->name('conversations.poll');

        Route::get('notifications', [PatientNotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/{notification}/read', [PatientNotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('notifications/read-all', [PatientNotificationController::class, 'markAllRead'])->name('notifications.read-all');

        Route::get('lgpd/requests', [PatientLgpdController::class, 'index'])->name('lgpd.index');
        Route::post('lgpd/requests', [PatientLgpdController::class, 'store'])->name('lgpd.store');

        Route::post('device-token', [DeviceTokenController::class, 'store'])->name('device-token.store');
        Route::delete('device-token', [DeviceTokenController::class, 'destroy'])->name('device-token.destroy');
    });

    $writeAbilities = 'ability:*,api:write';

    Route::middleware(['auth:sanctum', 'professional.api', 'throttle:120,1', $readAbilities])->group(function () {
        Route::post('/auth/logout', [AuthTokenController::class, 'logout'])->name('auth.logout');

        Route::get('/summary', SummaryController::class)->name('summary');

        Route::get('patients', [PatientController::class, 'index'])->name('patients.index');
        Route::get('patients/{patient}', [PatientController::class, 'show'])->name('patients.show');

        Route::get('therapy-sessions', [TherapySessionController::class, 'index'])->name('therapy-sessions.index');
        Route::get('therapy-sessions/{therapy_session}', [TherapySessionController::class, 'show'])->name('therapy-sessions.show');

        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');

        Route::get('clinical-records', [ClinicalRecordController::class, 'index'])->name('clinical-records.index');
        Route::get('clinical-records/{clinical_record}', [ClinicalRecordController::class, 'show'])->name('clinical-records.show');
    });

    Route::middleware(['auth:sanctum', 'professional.api', 'throttle:120,1', $writeAbilities])->group(function () {
        Route::post('patients', [PatientController::class, 'store'])->name('patients.store');
        Route::put('patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
        Route::patch('patients/{patient}', [PatientController::class, 'update'])->name('patients.update.patch');
        Route::delete('patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy');

        Route::post('therapy-sessions', [TherapySessionController::class, 'store'])->name('therapy-sessions.store');
        Route::put('therapy-sessions/{therapy_session}', [TherapySessionController::class, 'update'])->name('therapy-sessions.update');
        Route::patch('therapy-sessions/{therapy_session}', [TherapySessionController::class, 'update'])->name('therapy-sessions.update.patch');
        Route::delete('therapy-sessions/{therapy_session}', [TherapySessionController::class, 'destroy'])->name('therapy-sessions.destroy');

        Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::put('payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
        Route::patch('payments/{payment}', [PaymentController::class, 'update'])->name('payments.update.patch');
        Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

        Route::post('clinical-records', [ClinicalRecordController::class, 'store'])->name('clinical-records.store');
        Route::put('clinical-records/{clinical_record}', [ClinicalRecordController::class, 'update'])->name('clinical-records.update');
        Route::patch('clinical-records/{clinical_record}', [ClinicalRecordController::class, 'update'])->name('clinical-records.update.patch');
        Route::delete('clinical-records/{clinical_record}', [ClinicalRecordController::class, 'destroy'])->name('clinical-records.destroy');
    });
});
