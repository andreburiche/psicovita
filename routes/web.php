<?php

use App\Http\Controllers\ChatbotWidgetController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\AnamnesisFormController;
use App\Http\Controllers\PatientAnamnesisController;
use App\Http\Controllers\Api\CepLookupController;
use App\Http\Controllers\ClinicInvitationController;
use App\Http\Controllers\ClinicTeamController;
use App\Http\Controllers\ClinicalRecordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PatientPaymentController;
use App\Http\Controllers\PatientPortalActivationController;
use App\Http\Controllers\PatientPortalController;
use App\Http\Controllers\PatientPortalSessionController;
use App\Http\Controllers\PatientSupportConversationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScheduleBlockController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SessionScheduleExportController;
use App\Http\Controllers\TherapySessionController;
use App\Http\Controllers\TherapySessionVideoCallController;
use App\Http\Controllers\DocumentRequestController;
use App\Http\Controllers\PatientClinicalDocumentController;
use App\Http\Controllers\PatientScaleAssessmentController;
use App\Http\Controllers\PatientTherapeuticGoalController;
use App\Http\Controllers\PatientDocumentController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\Media\AvatarMediaController;
use App\Http\Controllers\Media\PublicStorageController;
use App\Http\Controllers\Admin\DataSubjectRequestController as AdminDataSubjectRequestController;
use App\Http\Controllers\Admin\ChatbotDashboardController;
use App\Http\Controllers\Admin\ChatbotIntentAdminController;
use App\Http\Controllers\Admin\AccessibilityReportController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\LandingPartnerController;
use App\Http\Controllers\Admin\LgpdMetricsController;
use App\Http\Controllers\Admin\SubscriptionPlanAdminController;
use App\Http\Controllers\Admin\ProfessionalSubscriptionAdminController;
use App\Http\Controllers\Admin\SiteSettingsController;
use App\Http\Controllers\Admin\WhatsAppIntegrationController;
use App\Http\Controllers\PatientLgpdController;
use App\Http\Controllers\ProfessionalAsaasWalletController;
use App\Http\Controllers\ProfessionalPaymentSettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionCheckoutController;
use App\Http\Controllers\SupportDeskController;
use App\Http\Controllers\UserProfessionalFileController;
use App\Http\Controllers\Webhooks\AsaasWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/asaas', AsaasWebhookController::class)->name('webhooks.asaas');

Route::get('/privacidade', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/privacidade/dpia-ia', [LegalController::class, 'dpiaAi'])->name('legal.dpia-ai');
Route::get('/termos', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/media/avatars/users/{user}', [AvatarMediaController::class, 'user'])
    ->name('media.user-avatar');
Route::get('/media/avatars/patients/{patient}', [AvatarMediaController::class, 'patient'])
    ->name('media.patient-avatar');
// Fallback: URLs /storage/... sem symlink public/storage (HostGator, etc.)
Route::get('/storage/{path}', [PublicStorageController::class, 'show'])
    ->where('path', '.*')
    ->name('storage.public');
Route::get('/sessao-video/{token}', [TherapySessionVideoCallController::class, 'guestJoin'])->name('session-video.guest');
Route::post('/sessao-video/{token}/consentimento', [TherapySessionVideoCallController::class, 'guestConsent'])
    ->middleware('throttle:20,1')
    ->name('session-video.consent');

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(auth()->user()->defaultAppRouteName())
        : app(LandingController::class)();
})->name('home');

Route::middleware(['auth', 'verified', 'professional', 'subscription.access'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('patients/{patient}/conversa', [ConversationController::class, 'startFromPatient'])
        ->name('patients.conversation');
    Route::post('patients/{patient}/portal-invite/resend', [PatientController::class, 'resendPortalInvite'])
        ->middleware('throttle:10,1')
        ->name('patients.portal-invite.resend');
    Route::resource('patients', PatientController::class);
    Route::resource('patients.document-requests', DocumentRequestController::class)
        ->scoped(['document_request' => 'patient_id']);
    Route::get('document-request-files/{document_request_file}/download', [DocumentRequestController::class, 'downloadFile'])
        ->name('document-request-files.download');
    Route::delete('document-request-files/{document_request_file}', [DocumentRequestController::class, 'destroyFile'])
        ->name('document-request-files.destroy');
    Route::get('patients/{patient}/document-requests/{document_request}/pdf', [DocumentRequestController::class, 'pdf'])
        ->name('patients.document-requests.pdf');
    Route::post('patients/{patient}/document-requests/{document_request}/send-email', [DocumentRequestController::class, 'sendEmail'])
        ->middleware('throttle:10,1')
        ->name('patients.document-requests.send-email');
    Route::post('patients/{patient}/document-requests/{document_request}/files', [DocumentRequestController::class, 'storeFile'])
        ->name('patients.document-requests.files.store');
    Route::post('patients/{patient}/documents', [PatientDocumentController::class, 'store'])
        ->name('patients.documents.store');
    Route::get('patients/{patient}/clinical-documents/create/{type}', [PatientClinicalDocumentController::class, 'create'])
        ->name('patients.clinical-documents.create')
        ->whereIn('type', ['atestado', 'declaracao', 'receita']);
    Route::get('patients/{patient}/clinical-documents/preview', [PatientClinicalDocumentController::class, 'previewUnavailable'])
        ->name('patients.clinical-documents.preview.unavailable');
    Route::post('patients/{patient}/clinical-documents/preview', [PatientClinicalDocumentController::class, 'preview'])
        ->name('patients.clinical-documents.preview');
    Route::get('patients/{patient}/clinical-documents/preview/{token}', [PatientClinicalDocumentController::class, 'showPreview'])
        ->name('patients.clinical-documents.preview.show')
        ->whereUuid('token');
    Route::post('patients/{patient}/clinical-documents', [PatientClinicalDocumentController::class, 'store'])
        ->name('patients.clinical-documents.store');
    Route::get('patients/{patient}/clinical-documents/{clinical_document}/pdf', [PatientClinicalDocumentController::class, 'pdf'])
        ->name('patients.clinical-documents.pdf');
    Route::get('patients/{patient}/scale-assessments/create/{scale}', [PatientScaleAssessmentController::class, 'create'])
        ->name('patients.scale-assessments.create')
        ->whereIn('scale', ['bai', 'bdi', 'stress']);
    Route::post('patients/{patient}/scale-assessments', [PatientScaleAssessmentController::class, 'store'])
        ->name('patients.scale-assessments.store');
    Route::delete('patients/{patient}/scale-assessments/{scale_assessment}', [PatientScaleAssessmentController::class, 'destroy'])
        ->name('patients.scale-assessments.destroy');
    Route::post('patients/{patient}/therapeutic-goals', [PatientTherapeuticGoalController::class, 'store'])
        ->name('patients.therapeutic-goals.store');
    Route::patch('patients/{patient}/therapeutic-goals/{therapeutic_goal}', [PatientTherapeuticGoalController::class, 'update'])
        ->name('patients.therapeutic-goals.update');
    Route::delete('patients/{patient}/therapeutic-goals/{therapeutic_goal}', [PatientTherapeuticGoalController::class, 'destroy'])
        ->name('patients.therapeutic-goals.destroy');
    Route::get('patient-documents/{patient_document}/download', [PatientDocumentController::class, 'download'])
        ->name('patient-documents.download');
    Route::delete('patient-documents/{patient_document}', [PatientDocumentController::class, 'destroy'])
        ->name('patient-documents.destroy');
    Route::post('patients/{patient}/anamnesis', [PatientAnamnesisController::class, 'store'])
        ->name('patients.anamnesis.store');
    Route::patch('therapy-sessions/{therapy_session}/status', [TherapySessionController::class, 'updateStatus'])
        ->name('therapy-sessions.update-status');
    Route::post('therapy-sessions/{therapy_session}/observer-invite', [TherapySessionController::class, 'resendObserverInvite'])
        ->middleware('throttle:10,1')
        ->name('therapy-sessions.observer-invite');
    Route::post('therapy-sessions/{therapy_session}/participants/{participant}/invite', [TherapySessionController::class, 'resendParticipantInvite'])
        ->middleware('throttle:10,1')
        ->name('therapy-sessions.participants.invite');
    Route::get('therapy-sessions/export/pdf', [SessionScheduleExportController::class, 'pdfSessions'])
        ->name('therapy-sessions.export.pdf');
    Route::get('therapy-sessions/export/excel', [SessionScheduleExportController::class, 'excelSessions'])
        ->name('therapy-sessions.export.excel');
    Route::resource('therapy-sessions', TherapySessionController::class);
    Route::get('therapy-sessions/{therapy_session}/video', [TherapySessionVideoCallController::class, 'room'])
        ->name('therapy-sessions.video.room');
    Route::post('therapy-sessions/{therapy_session}/video/start', [TherapySessionVideoCallController::class, 'start'])
        ->name('therapy-sessions.video.start');
    Route::middleware('subscription.feature:use_ai')->group(function () {
        Route::post('therapy-sessions/{therapy_session}/video/finish', [TherapySessionVideoCallController::class, 'finish'])
            ->middleware('throttle:10,1')
            ->name('therapy-sessions.video.finish');
        Route::get('therapy-sessions/{therapy_session}/video/review', [TherapySessionVideoCallController::class, 'review'])
            ->name('therapy-sessions.video.review');
        Route::post('therapy-sessions/{therapy_session}/video/regenerate-devolutiva', [TherapySessionVideoCallController::class, 'regenerateDevolutiva'])
            ->middleware('throttle:15,1')
            ->name('therapy-sessions.video.regenerate-devolutiva');
        Route::post('therapy-sessions/{therapy_session}/video/save-record', [TherapySessionVideoCallController::class, 'saveToRecord'])
            ->middleware('throttle:20,1')
            ->name('therapy-sessions.video.save-record');
    });
    Route::patch('payments/{payment}/quick', [PaymentController::class, 'quickUpdate'])->name('payments.quick-update');
    Route::post('payments/{payment}/confirmar-pix-manual', [PaymentController::class, 'confirmManual'])
        ->name('payments.confirm-manual');
    Route::resource('payments', PaymentController::class);
    Route::resource('clinical-records', ClinicalRecordController::class);

    Route::get('/api/cep/{cep}', CepLookupController::class)
        ->middleware('throttle:40,1')
        ->name('api.cep');

    Route::resource('anamnesis-forms', AnamnesisFormController::class);
    Route::resource('schedule-blocks', ScheduleBlockController::class)->except(['show']);

    Route::get('/agenda', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::get('/agenda/export/pdf', [SessionScheduleExportController::class, 'pdfSchedule'])
        ->name('schedule.export.pdf');
    Route::get('/agenda/export/excel', [SessionScheduleExportController::class, 'excelSchedule'])
        ->name('schedule.export.excel');
    Route::get('/relatorios', [ReportController::class, 'index'])->name('reports.index');

    Route::get('/ia-assistente', [AiAssistantController::class, 'index'])
        ->middleware('subscription.feature:use_ai')
        ->name('ai.index');
    Route::get('/ia/registros/{aiRequest}', [AiAssistantController::class, 'show'])
        ->middleware('subscription.feature:use_ai')
        ->name('ai.show');
    Route::post('/ia/transcrever-audio', [AiAssistantController::class, 'transcribe'])
        ->middleware(['subscription.feature:use_ai', 'throttle:20,1'])
        ->name('ai.transcribe');
    Route::post('/ia/gerar-texto', [AiAssistantController::class, 'generateText'])
        ->middleware(['subscription.feature:use_ai', 'throttle:30,1'])
        ->name('ai.generate-text');
    Route::post('/ia/recomendar-terapeuta', [AiAssistantController::class, 'recommendTherapist'])
        ->middleware(['subscription.feature:use_ai', 'throttle:30,1'])
        ->name('ai.recommend-therapist');
    Route::post('/ia/salvar-prontuario', [AiAssistantController::class, 'saveToRecord'])
        ->middleware(['subscription.feature:use_ai', 'throttle:30,1'])
        ->name('ai.save-record');
    Route::delete('/ia/registros/{aiRequest}', [AiAssistantController::class, 'destroy'])
        ->middleware('throttle:60,1')
        ->name('ai.destroy');

    Route::post('/equipa/convites', [ClinicTeamController::class, 'storeInvite'])
        ->middleware(['subscription.feature:multi_user', 'throttle:10,1'])
        ->name('clinic.invitations.store');
    Route::delete('/equipa/membros/{member}', [ClinicTeamController::class, 'destroyMember'])
        ->middleware('subscription.feature:multi_user')
        ->name('clinic.members.destroy');
});

Route::get('/convite-equipa/{token}', [ClinicInvitationController::class, 'show'])->name('clinic.invitations.show');

Route::get('/portal/activar/{token}', [PatientPortalActivationController::class, 'show'])->name('patient-portal.activate.show');
Route::post('/portal/activar/{token}', [PatientPortalActivationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('patient-portal.activate.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/notificacoes/{notification}/abrir', [NotificationController::class, 'open'])
        ->name('notifications.open');
    Route::post('/notificacoes/marcar-todas-lidas', [NotificationController::class, 'markAllRead'])
        ->name('notifications.mark-all-read');

    Route::post('/convite-equipa/{token}/aceitar', [ClinicInvitationController::class, 'accept'])
        ->name('clinic.invitations.accept');

    Route::get('/area-paciente', [PatientPortalController::class, 'index'])->name('patient.home');

    Route::middleware('patient.portal')->group(function () {
        Route::get('/area-paciente/privacidade', [PatientLgpdController::class, 'index'])->name('patient.lgpd.index');
        Route::post('/area-paciente/privacidade', [PatientLgpdController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('patient.lgpd.store');
        Route::get('/area-paciente/privacidade/exportar', [PatientLgpdController::class, 'export'])
            ->middleware('throttle:5,60')
            ->name('patient.lgpd.export');
        Route::get('/area-paciente/privacidade/exportar-pdf', [PatientLgpdController::class, 'exportPdf'])
            ->middleware('throttle:5,60')
            ->name('patient.lgpd.export.pdf');

        Route::get('/area-paciente/pagamentos', [PatientPaymentController::class, 'index'])->name('patient.payments.index');
        Route::get('/area-paciente/consultas-online', [PatientPortalSessionController::class, 'index'])->name('patient.sessions.index');
        Route::get('/area-paciente/consultas-online/{therapy_session}/entrar', [PatientPortalSessionController::class, 'join'])->name('patient.sessions.join');
        Route::get('/area-paciente/pagamentos/{payment}', [PatientPaymentController::class, 'show'])->name('patient.payments.show');
        Route::post('/area-paciente/pagamentos/{payment}/pagar', [PatientPaymentController::class, 'pay'])
            ->middleware('throttle:10,1')
            ->name('patient.payments.pay');
        Route::post('/area-paciente/pagamentos/{payment}/ja-paguei', [PatientPaymentController::class, 'alreadyPaid'])
            ->middleware('throttle:10,1')
            ->name('patient.payments.already-paid');
    });

    Route::middleware('lgpd.admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/lgpd/metricas', [LgpdMetricsController::class, 'index'])->name('lgpd.metrics');
        Route::get('/lgpd/auditoria', [AuditLogController::class, 'index'])->name('lgpd.audit');
        Route::get('/lgpd/auditoria/exportar', [AuditLogController::class, 'export'])
            ->middleware('throttle:10,60')
            ->name('lgpd.audit.export');
        Route::get('/lgpd/acessibilidade', [AccessibilityReportController::class, 'index'])->name('lgpd.accessibility');
        Route::get('/lgpd/solicitacoes', [AdminDataSubjectRequestController::class, 'index'])->name('lgpd.requests.index');
        Route::get('/lgpd/solicitacoes/{data_subject_request}', [AdminDataSubjectRequestController::class, 'show'])->name('lgpd.requests.show');
        Route::patch('/lgpd/solicitacoes/{data_subject_request}', [AdminDataSubjectRequestController::class, 'update'])->name('lgpd.requests.update');
        Route::get('/lgpd/solicitacoes/{data_subject_request}/exportar-dados', [AdminDataSubjectRequestController::class, 'exportUserData'])
            ->middleware('throttle:10,60')
            ->name('lgpd.requests.export');

        Route::get('/site/redes-sociais', [SiteSettingsController::class, 'edit'])->name('site.settings');
        Route::patch('/site/redes-sociais', [SiteSettingsController::class, 'update'])->name('site.settings.update');
        Route::get('/site/planos', [SubscriptionPlanAdminController::class, 'index'])->name('site.plans');
        Route::patch('/site/planos/{plan}', [SubscriptionPlanAdminController::class, 'update'])->name('site.plans.update');
        Route::get('/assinaturas', [ProfessionalSubscriptionAdminController::class, 'index'])->name('subscriptions.index');
        Route::get('/assinaturas/{subscription}/validar', [ProfessionalSubscriptionAdminController::class, 'edit'])->name('subscriptions.validate');
        Route::patch('/assinaturas/{subscription}', [ProfessionalSubscriptionAdminController::class, 'update'])->name('subscriptions.update');
        Route::post('/assinaturas/{subscription}/cortesia', [ProfessionalSubscriptionAdminController::class, 'grantComplimentary'])->name('subscriptions.complimentary.grant');
        Route::delete('/assinaturas/{subscription}/cortesia', [ProfessionalSubscriptionAdminController::class, 'revokeComplimentary'])->name('subscriptions.complimentary.revoke');
        Route::get('/site/parceiros', [LandingPartnerController::class, 'index'])->name('site.partners');
        Route::post('/site/parceiros', [LandingPartnerController::class, 'store'])->name('site.partners.store');
        Route::patch('/site/parceiros/{partner}', [LandingPartnerController::class, 'update'])->name('site.partners.update');
        Route::delete('/site/parceiros/{partner}', [LandingPartnerController::class, 'destroy'])->name('site.partners.destroy');

        Route::get('/integracoes/whatsapp', [WhatsAppIntegrationController::class, 'index'])->name('integrations.whatsapp');
        Route::post('/integracoes/whatsapp/testar', [WhatsAppIntegrationController::class, 'testConnection'])
            ->middleware('throttle:10,1')
            ->name('integrations.whatsapp.test');
        Route::post('/integracoes/whatsapp/webhook', [WhatsAppIntegrationController::class, 'syncWebhook'])
            ->middleware('throttle:10,1')
            ->name('integrations.whatsapp.webhook-sync');

        Route::get('/suporte/metricas', [ChatbotDashboardController::class, 'index'])->name('support.metrics');
        Route::get('/chatbot/intents', [ChatbotIntentAdminController::class, 'index'])->name('chatbot.intents.index');
        Route::post('/chatbot/intents', [ChatbotIntentAdminController::class, 'store'])->name('chatbot.intents.store');
        Route::patch('/chatbot/intents/{chatbotIntent}', [ChatbotIntentAdminController::class, 'update'])->name('chatbot.intents.update');
        Route::delete('/chatbot/intents/{chatbotIntent}', [ChatbotIntentAdminController::class, 'destroy'])->name('chatbot.intents.destroy');
    });

    Route::middleware('support.desk')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/suporte', [SupportDeskController::class, 'index'])->name('support.index');
        Route::get('/suporte/{supportConversation}', [SupportDeskController::class, 'show'])->name('support.show');
        Route::get('/suporte/{supportConversation}/poll', [SupportDeskController::class, 'poll'])
            ->middleware('throttle:60,1')
            ->name('support.poll');
        Route::post('/suporte/{supportConversation}/assumir', [SupportDeskController::class, 'assign'])->name('support.assign');
        Route::post('/suporte/{supportConversation}/mensagens', [SupportDeskController::class, 'storeMessage'])
            ->middleware('throttle:30,1')
            ->name('support.messages.store');
        Route::post('/suporte/{supportConversation}/transferir', [SupportDeskController::class, 'transfer'])->name('support.transfer');
        Route::post('/suporte/{supportConversation}/resolver', [SupportDeskController::class, 'resolve'])->name('support.resolve');
        Route::post('/suporte/{supportConversation}/encerrar', [SupportDeskController::class, 'close'])->name('support.close');
    });

    Route::get('/conversas/apoio', [PatientSupportConversationController::class, 'index'])->name('conversations.support.index');
    Route::post('/conversas/apoio/mensagens', [PatientSupportConversationController::class, 'storeMessage'])
        ->middleware('throttle:30,1')
        ->name('conversations.support.messages.store');
    Route::get('/conversas/apoio/poll', [PatientSupportConversationController::class, 'poll'])
        ->middleware('throttle:60,1')
        ->name('conversations.support.poll');

    Route::get('/conversas', [ConversationController::class, 'index'])->name('conversations.index');
    Route::get('/conversas/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::post('/conversas/iniciar', [ConversationController::class, 'start'])->name('conversations.start');
    Route::post('/conversas/{conversation}/mensagens', [ConversationController::class, 'storeMessage'])
        ->middleware('throttle:30,1')
        ->name('conversations.messages.store');
    Route::post('/conversas/{conversation}/whatsapp', [ConversationController::class, 'toggleWhatsApp'])
        ->name('conversations.whatsapp.toggle');
    Route::post('/conversas/{conversation}/whatsapp/consentir', [ConversationController::class, 'grantWhatsappConsent'])
        ->name('conversations.whatsapp.consent');
    Route::post('/conversas/{conversation}/whatsapp/lembrete-consentimento', [ConversationController::class, 'remindWhatsappConsent'])
        ->name('conversations.whatsapp.consent-remind');
    Route::post('/conversas/{conversation}/whatsapp/revogar', [ConversationController::class, 'revokeWhatsappConsent'])
        ->name('conversations.whatsapp.revoke');
    Route::get('/conversas/{conversation}/poll', [ConversationController::class, 'poll'])
        ->middleware('throttle:60,1')
        ->name('conversations.poll');
    Route::post('/conversas/{conversation}/digitando', [ConversationController::class, 'typingPulse'])
        ->middleware('throttle:120,1')
        ->name('conversations.typing');
    Route::get('/conversas/{conversation}/exportar-pdf', [ConversationController::class, 'exportPdf'])
        ->name('conversations.export.pdf');
    Route::post('/conversas/{conversation}/arquivar-prontuario', [ConversationController::class, 'saveToClinicalRecord'])
        ->name('conversations.archive.record');
    Route::get('/conversas/{conversation}/anexos/{attachment}', [ConversationController::class, 'downloadAttachment'])
        ->name('conversations.attachments.download');

    Route::prefix('chatbot')->name('chatbot.')->middleware('throttle:60,1')->group(function () {
        Route::get('/widget', [ChatbotWidgetController::class, 'show'])->name('widget.show');
        Route::post('/widget/mensagens', [ChatbotWidgetController::class, 'storeMessage'])
            ->middleware('throttle:30,1')
            ->name('widget.messages.store');
        Route::get('/widget/poll', [ChatbotWidgetController::class, 'poll'])->name('widget.poll');
    });

    Route::get('/mensagens', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/mensagens', [MessageController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('messages.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/assinatura', [SubscriptionCheckoutController::class, 'index'])->name('subscription.checkout');
    Route::post('/assinatura', [SubscriptionCheckoutController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('subscription.checkout.store');
    Route::delete('/assinatura', [SubscriptionCheckoutController::class, 'destroy'])
        ->middleware('throttle:10,1')
        ->name('subscription.checkout.cancel');

    Route::post('/profile/asaas-wallet', [ProfessionalAsaasWalletController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('profile.asaas-wallet.provision');
    Route::post('/profile/recebimento', [ProfessionalPaymentSettingsController::class, 'update'])
        ->middleware('throttle:20,1')
        ->name('profile.payment-settings.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/professional-files', [UserProfessionalFileController::class, 'store'])->name('profile.professional-files.store');
    Route::get('/profile/professional-files/{professionalFile}/download', [UserProfessionalFileController::class, 'download'])->name('profile.professional-files.download');
    Route::delete('/profile/professional-files/{professionalFile}', [UserProfessionalFileController::class, 'destroy'])->name('profile.professional-files.destroy');
});

require __DIR__.'/auth.php';
