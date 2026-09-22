<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\MercurialeArticle;
use App\Models\PaymentTransaction;
use App\Models\PlatformSetting;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'tenants' => Tenant::query()->count(),
                'users' => User::query()->count(),
                'paid_tenants' => Tenant::query()->where('plan', '!=', 'free')->count(),
                'documents' => Document::withoutGlobalScopes()->count(),
                'articles' => MercurialeArticle::query()->count(),
                'open_tickets' => SupportTicket::withoutGlobalScopes()->where('status', 'open')->count(),
                'revenue_pending' => PaymentTransaction::query()->where('status', 'pending')->sum('amount'),
                'revenue_paid' => PaymentTransaction::query()->where('status', 'paid')->sum('amount'),
                'maintenance' => PlatformSetting::maintenanceEnabled(),
            ],
            'maintenanceMessage' => PlatformSetting::maintenanceMessage(),
            'recentTenants' => Tenant::query()->latest()->limit(5)->get(['id', 'name', 'plan', 'created_at']),
        ]);
    }
}
