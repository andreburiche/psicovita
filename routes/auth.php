<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\SocialRegistrationController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Middleware\ValidateEmailVerificationSignature;
use Illuminate\Support\Facades\Route;

/*
 * Confirmação por ligação assinada: fora de `auth` para funcionar mesmo com outra sessão
 * iniciada no browser (ex.: várias contas no mesmo computador).
 */
Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
    ->middleware([ValidateEmailVerificationSignature::class, 'throttle:6,1'])
    ->name('verification.verify');

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->middleware('throttle:12,1')
        ->whereIn('provider', \App\Support\SocialAuthProvider::all())
        ->name('social.redirect');

    Route::get('auth/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->middleware('throttle:12,1')
        ->whereIn('provider', \App\Support\SocialAuthProvider::all())
        ->name('social.callback');

    Route::get('auth/social/complete', [SocialRegistrationController::class, 'create'])
        ->name('social.register.complete');

    Route::post('auth/social/complete', [SocialRegistrationController::class, 'store'])
        ->name('social.register.store');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('logout', [AuthenticatedSessionController::class, 'confirmDestroy'])
        ->name('logout.confirm');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
