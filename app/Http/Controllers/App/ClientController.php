<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Services\Billing\PlanQuotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Client::class);

        $q = trim((string) $request->get('q', ''));

        $clients = Client::query()
            ->when($q !== '', fn ($query) => $query->where(function ($inner) use ($q): void {
                $inner->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('company', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('App/Clients/Index', [
            'clients' => $clients,
            'filters' => ['q' => $q],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $q = trim((string) $request->get('q', ''));
        $filename = 'clients-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($q): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'name', 'email', 'phone', 'company', 'city', 'address'], ';');

            Client::query()
                ->when($q !== '', fn ($query) => $query->where(function ($inner) use ($q): void {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('company', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                }))
                ->latest()
                ->chunk(200, function ($clients) use ($out): void {
                    foreach ($clients as $client) {
                        fputcsv($out, [
                            $client->id,
                            $client->name,
                            $client->email,
                            $client->phone,
                            $client->company,
                            $client->city,
                            $client->address,
                        ], ';');
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Client::class);

        return Inertia::render('App/Clients/Form', [
            'client' => null,
        ]);
    }

    public function store(Request $request, PlanQuotaService $quotas): RedirectResponse
    {
        $this->authorize('create', Client::class);

        $tenant = $request->user()->currentTenant;
        abort_unless($tenant, 403);

        try {
            $quotas->ensureClientQuota($tenant, $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $data = $this->validated($request);
        $client = Client::query()->create($data);

        ActivityLog::record('client.created', $client, ['name' => $client->name]);

        return redirect()->route('app.clients.index')->with('success', 'Client créé.');
    }

    public function edit(Client $client): Response
    {
        $this->authorize('update', $client);

        return Inertia::render('App/Clients/Form', [
            'client' => $client,
        ]);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $client->update($this->validated($request));

        ActivityLog::record('client.updated', $client);

        return redirect()->route('app.clients.index')->with('success', 'Client mis à jour.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        ActivityLog::record('client.deleted', null, ['name' => $client->name, 'id' => $client->id]);
        $client->delete();

        return back()->with('success', 'Client supprimé.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
