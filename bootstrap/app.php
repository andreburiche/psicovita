<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        $dailyBriefingTime = (string) config('psiconecta.agenda.daily_briefing_time', '07:00');
        $schedule->command('psiconecta:professional-daily-agenda')->dailyAt($dailyBriefingTime);
        $schedule->command('psiconecta:professional-session-upcoming')->everyMinute();
        $schedule->command('psiconecta:session-reminders')->dailyAt('18:00');
        $schedule->command('psiconecta:expire-subscriptions')->dailyAt('00:30');
        $schedule->command('psiconecta:subscription-reminders')->dailyAt('08:00');
        $schedule->command('psiconecta:payment-reminders')->dailyAt('09:00');
        $schedule->command('psiconecta:compliance-prune')->monthlyOn(1, '03:00');
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'professional' => \App\Http\Middleware\EnsureProfessional::class,
            'patient.portal' => \App\Http\Middleware\EnsurePatientPortal::class,
            'lgpd.admin' => \App\Http\Middleware\EnsureLgpdAdmin::class,
            'support.desk' => \App\Http\Middleware\EnsureSupportDeskAccess::class,
            'professional.api' => \App\Http\Middleware\EnsureProfessionalApi::class,
            'patient.api' => \App\Http\Middleware\EnsurePatientApi::class,
            'subscription.feature' => \App\Http\Middleware\EnsureSubscriptionFeature::class,
            'subscription.access' => \App\Http\Middleware\EnsureProfessionalSubscriptionAccess::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
