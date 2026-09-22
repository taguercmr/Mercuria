<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\App\CatalogueController;
use App\Http\Controllers\App\ClientController;
use App\Http\Controllers\App\DashboardController as AppDashboardController;
use App\Http\Controllers\App\DeveloperController;
use App\Http\Controllers\App\DocumentController;
use App\Http\Controllers\App\OnboardingController;
use App\Http\Controllers\App\SettingsController;
use App\Http\Controllers\App\SubscriptionController;
use App\Http\Controllers\App\TicketController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Webhooks\PaymentWebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Landing', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'mercurialeYear' => config('mercuria.mercuriale_year'),
        'source' => config('mercuria.mercuriale_source'),
        'plans' => config('mercuria.plans'),
    ]);
})->name('landing');

Route::get('/health', HealthController::class)->name('health');

Route::post('/webhooks/fapshi', [PaymentWebhookController::class, 'fapshi'])->name('webhooks.fapshi');
Route::post('/webhooks/cinetpay', [PaymentWebhookController::class, 'cinetpay'])->name('webhooks.cinetpay');

Route::get('/invitations/{token}', [\App\Http\Controllers\App\TeamController::class, 'showAccept'])
    ->name('invitations.show');
Route::post('/invitations/{token}', [\App\Http\Controllers\App\TeamController::class, 'accept'])
    ->name('invitations.accept');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'create'])->name('onboarding.create');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');

    Route::middleware(['tenant'])->prefix('app')->name('app.')->group(function () {
        Route::get('/dashboard', AppDashboardController::class)->name('dashboard');
        Route::get('/onboarding/guide', [OnboardingController::class, 'guide'])->name('onboarding.guide');
        Route::post('/onboarding/skip', [OnboardingController::class, 'skip'])->name('onboarding.skip');

        Route::get('/catalogue', [CatalogueController::class, 'index'])->name('catalogue.index');
        Route::post('/catalogue/{article}/favorite', [\App\Http\Controllers\App\FavoriteController::class, 'store'])->name('catalogue.favorite');
        Route::delete('/catalogue/{article}/favorite', [\App\Http\Controllers\App\FavoriteController::class, 'destroy'])->name('catalogue.unfavorite');

        Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
        Route::get('/clients/export', [ClientController::class, 'export'])->name('clients.export');

        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/export', [DocumentController::class, 'export'])->name('documents.export');
        Route::get('/documents/articles/search', [DocumentController::class, 'searchArticles'])->name('documents.articles.search');
        Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
        Route::get('/documents/{document}/pdf', [DocumentController::class, 'pdf'])->name('documents.pdf');

        Route::get('/templates', [\App\Http\Controllers\App\DocumentTemplateController::class, 'index'])->name('templates.index');
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::get('/subscription', [SubscriptionController::class, 'show'])->name('subscription.show');
        Route::get('/subscription/return', [SubscriptionController::class, 'return'])->name('subscription.return');
        Route::get('/developers', [DeveloperController::class, 'index'])->name('developers.index');
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('/team', [\App\Http\Controllers\App\TeamController::class, 'index'])->name('team.index');
        Route::get('/notifications', [\App\Http\Controllers\App\NotificationController::class, 'index'])
            ->name('notifications.index');
        Route::post('/notifications/read-all', [\App\Http\Controllers\App\NotificationController::class, 'markAllRead'])
            ->name('notifications.readAll');
        Route::post('/notifications/{id}/read', [\App\Http\Controllers\App\NotificationController::class, 'markRead'])
            ->name('notifications.read');

        Route::post('/tenant/switch/{tenant}', \App\Http\Controllers\App\TenantSwitchController::class)
            ->name('tenant.switch');

        // Mutations: owner + editor only (viewers are read-only)
        Route::middleware('tenant.role:owner,editor')->group(function () {
            Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
            Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
            Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
            Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
            Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

            Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
            Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
            Route::post('/documents/{document}/send', [DocumentController::class, 'send'])->name('documents.send');
            Route::post('/documents/{document}/template', [\App\Http\Controllers\App\DocumentTemplateController::class, 'storeFromDocument'])->name('documents.template');
            Route::post('/documents/{document}/duplicate', [DocumentController::class, 'duplicate'])->name('documents.duplicate');
            Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
            Route::put('/documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
            Route::post('/documents/{document}/convert', [DocumentController::class, 'convert'])->name('documents.convert');

            Route::delete('/templates/{template}', [\App\Http\Controllers\App\DocumentTemplateController::class, 'destroy'])->name('templates.destroy');
            Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
            Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
            Route::post('/developers/keys', [DeveloperController::class, 'storeKey'])->name('developers.keys.store');
            Route::delete('/developers/keys/{apiKey}', [DeveloperController::class, 'revokeKey'])->name('developers.keys.revoke');
            Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
        });

        Route::post('/team/invite', [\App\Http\Controllers\App\TeamController::class, 'invite'])
            ->middleware('tenant.role:owner')
            ->name('team.invite');
        Route::put('/team/{user}/role', [\App\Http\Controllers\App\TeamController::class, 'updateRole'])
            ->middleware('tenant.role:owner')
            ->name('team.role');
        Route::delete('/team/{user}', [\App\Http\Controllers\App\TeamController::class, 'remove'])
            ->middleware('tenant.role:owner')
            ->name('team.remove');
    });

    Route::get('/dashboard', fn () => redirect()->route('app.dashboard'))->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'platform.admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::put('/maintenance', [MaintenanceController::class, 'update'])->name('maintenance.update');
        Route::get('/tickets', [\App\Http\Controllers\Admin\TicketController::class, 'index'])->name('tickets.index');
        Route::put('/tickets/{ticket}', [\App\Http\Controllers\Admin\TicketController::class, 'update'])->name('tickets.update');
        Route::get('/tenants', [\App\Http\Controllers\Admin\TenantController::class, 'index'])->name('tenants.index');
        Route::put('/tenants/{tenant}', [\App\Http\Controllers\Admin\TenantController::class, 'update'])->name('tenants.update');
        Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
        Route::put('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
    });

require __DIR__.'/auth.php';
