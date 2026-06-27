<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Models\TherapySession;
use App\Observers\TherapySessionObserver;
use App\Repositories\Contracts\DocumentRequestFileRepositoryInterface;
use App\Repositories\Contracts\DocumentRequestRepositoryInterface;
use App\Repositories\Contracts\PatientDocumentRepositoryInterface;
use App\Repositories\EloquentDocumentRequestFileRepository;
use App\Repositories\EloquentDocumentRequestRepository;
use App\Repositories\EloquentPatientDocumentRepository;
use App\Auth\EmailHashUserProvider;
use App\Services\Gateways\AsaasGatewayService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DocumentRequestRepositoryInterface::class, EloquentDocumentRequestRepository::class);
        $this->app->bind(DocumentRequestFileRepositoryInterface::class, EloquentDocumentRequestFileRepository::class);
        $this->app->bind(PatientDocumentRepositoryInterface::class, EloquentPatientDocumentRepository::class);
        $this->app->bind(PaymentGatewayInterface::class, AsaasGatewayService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        TherapySession::observe(TherapySessionObserver::class);

        Auth::provider('eloquent-email-hash', function ($app, array $config) {
            return new EmailHashUserProvider($app['hash'], $config['model']);
        });

        /*
         * Assinatura relativa: o HMAC ignora o host (localhost vs 127.0.0.1, porta, etc.),
         * evitando 403 "Invalid signature" quando APP_URL não coincide com o URL no browser.
         */
        VerifyEmail::createUrlUsing(function (object $notifiable): string {
            $expires = Carbon::now()->addMinutes((int) Config::get('auth.verification.expire', 60));
            $signedRelative = URL::temporarySignedRoute(
                'verification.verify',
                $expires,
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
                false,
            );

            return URL::to($signedRelative);
        });

        VerifyEmail::toMailUsing(function (object $notifiable, string $verificationUrl): MailMessage {
            $appName = (string) config('app.name', 'PsiConecta');

            return (new MailMessage)
                ->subject($appName.' — '.__('Confirmar o seu e-mail'))
                ->view([
                    'html' => 'emails.verify-email',
                    'text' => 'emails.verify-email-text',
                ], [
                    'userName' => $notifiable->name,
                    'verificationUrl' => $verificationUrl,
                    'appName' => $appName,
                ]);
        });

        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            return URL::to(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $appName = (string) config('app.name', 'PsiConecta');
            $resetUrl = URL::to(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject($appName.' — '.__('Redefinir palavra-passe'))
                ->view([
                    'html' => 'emails.reset-password',
                    'text' => 'emails.reset-password-text',
                ], [
                    'userName' => $notifiable->name,
                    'resetUrl' => $resetUrl,
                    'appName' => $appName,
                    'expireMinutes' => (int) Config::get('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60),
                ]);
        });

        View::composer('layouts.partials.app-sidebar', function ($view): void {
            $user = auth()->user();

            if ($user?->isProfessional()) {
                $view->with('patientQuota', app(SubscriptionService::class)->patientQuotaContext($user));
            } else {
                $view->with('patientQuota', [
                    'limited' => false,
                    'limit' => null,
                    'count' => 0,
                    'remaining' => null,
                    'at_limit' => false,
                    'near_limit' => false,
                ]);
            }

            $view->with(
                'supportPendingCount',
                $user?->canAccessSupportDesk()
                    ? app(\App\Services\Chatbot\SupportDeskService::class)->pendingCount()
                    : 0,
            );
        });
    }
}
