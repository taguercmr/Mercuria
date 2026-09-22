<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Mail\DocumentSentMail;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Document;
use App\Models\DocumentLine;
use App\Models\DocumentTemplate;
use App\Models\MercurialeArticle;
use App\Services\Billing\PlanQuotaService;
use App\Services\Documents\DocumentNumberGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', Document::class);

        $type = $request->get('type');
        $status = $request->get('status');

        $documents = Document::query()
            ->with(['client:id,name', 'creator:id,name'])
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('App/Documents/Index', [
            'documents' => $documents,
            'filters' => [
                'type' => $type,
                'status' => $status,
            ],
            'types' => Document::TYPES,
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $this->authorize('create', Document::class);

        $type = $request->get('type', Document::TYPE_QUOTE);
        if (! array_key_exists($type, Document::TYPES)) {
            $type = Document::TYPE_QUOTE;
        }

        $prefillLines = [];
        if ($articleId = $request->get('article')) {
            $article = MercurialeArticle::query()->find($articleId);
            if ($article) {
                $prefillLines[] = [
                    'mercuriale_article_id' => $article->id,
                    'reference' => $article->reference,
                    'designation' => $article->designation,
                    'unit' => $article->unit ?? 'PIECE',
                    'quantity' => 1,
                    'unit_price' => $article->unit_price,
                ];
            }
        }

        $template = null;
        if ($templateId = $request->get('template')) {
            $template = DocumentTemplate::query()->find($templateId);
            if ($template) {
                $type = $template->type;
                $prefillLines = $template->lines ?? [];
            }
        }

        return Inertia::render('App/Documents/Form', [
            'document' => $template ? [
                'type' => $template->type,
                'title' => $template->title,
                'notes' => $template->notes,
                'terms' => $template->terms,
                'tax_rate' => $template->tax_rate,
                'lines' => $prefillLines,
                'status' => 'draft',
            ] : null,
            'type' => $type,
            'types' => Document::TYPES,
            'clients' => Client::query()->orderBy('name')->get(['id', 'name', 'company']),
            'prefillLines' => $prefillLines,
            'templates' => DocumentTemplate::query()->latest()->get(['id', 'name', 'type']),
        ]);
    }

    public function store(Request $request, DocumentNumberGenerator $numbers, PlanQuotaService $quotas): RedirectResponse
    {
        $this->authorize('create', Document::class);

        $data = $this->validated($request);
        $tenant = $request->user()->currentTenant;
        abort_unless($tenant, 403);

        try {
            $quotas->ensureDocumentQuota($tenant);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $document = DB::transaction(function () use ($data, $request, $numbers, $tenant, $quotas) {
            $document = Document::query()->create([
                'tenant_id' => $tenant->id,
                'client_id' => $data['client_id'] ?? null,
                'created_by' => $request->user()->id,
                'type' => $data['type'],
                'number' => $data['status'] === 'issued'
                    ? $numbers->next($tenant, $data['type'])
                    : null,
                'status' => $data['status'],
                'currency' => $data['currency'] ?? $tenant->currency ?? 'XAF',
                'title' => $data['title'] ?? null,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'issue_date' => $data['issue_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? null,
                'tax_rate' => $data['tax_rate'] ?? 0,
                'issued_at' => $data['status'] === 'issued' ? now() : null,
            ]);

            $this->syncLines($document, $data['lines'] ?? []);
            $document->recalculateTotals();
            $quotas->trackDocumentCreated($tenant);

            return $document;
        });

        ActivityLog::record('document.created', $document, [
            'type' => $document->type,
            'number' => $document->number,
            'status' => $document->status,
        ]);

        return redirect()
            ->route('app.documents.show', $document)
            ->with('success', 'Document enregistré.');
    }

    public function show(Document $document): InertiaResponse
    {
        $this->authorize('view', $document);

        $document->load(['client', 'lines.mercurialeArticle', 'creator']);

        return Inertia::render('App/Documents/Show', [
            'document' => $document,
            'types' => Document::TYPES,
            'tenant' => $document->tenant()->first()?->only([
                'name', 'email', 'phone', 'logo_path', 'letterhead', 'footer_text', 'signature_path', 'currency',
            ]),
        ]);
    }

    public function pdf(Document $document): Response
    {
        $document->load(['client', 'lines']);
        $tenant = $document->tenant()->first();

        $pdf = Pdf::loadView('pdf.document', [
            'document' => $document,
            'tenant' => $tenant,
            'typeLabel' => $document->typeLabel(),
        ])->setPaper('a4');

        $filename = ($document->number ?: 'document-'.$document->id).'.pdf';

        return $pdf->download($filename);
    }

    public function send(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('send', $document);

        $document->load(['client', 'lines']);
        $tenant = $document->tenant()->first();
        abort_unless($tenant, 403);

        $data = $request->validate([
            'email' => ['nullable', 'email'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $email = $data['email'] ?? $document->client?->email;
        if (! $email) {
            return back()->with('error', 'Aucune email client. Ajoutez un email au client ou saisissez-en un.');
        }

        Mail::to($email)->send(new DocumentSentMail(
            document: $document,
            tenant: $tenant,
            customMessage: $data['message'] ?? null,
        ));

        if ($document->status === 'draft') {
            $document->update(['status' => 'sent']);
        } elseif ($document->status === 'issued') {
            $document->update(['status' => 'sent']);
        }

        ActivityLog::record('document.sent', $document, ['email' => $email]);

        return back()->with('success', "Document envoye a {$email}.");
    }

    public function edit(Document $document): InertiaResponse
    {
        $this->authorize('update', $document);

        $document->load('lines');

        return Inertia::render('App/Documents/Form', [
            'document' => $document,
            'type' => $document->type,
            'types' => Document::TYPES,
            'clients' => Client::query()->orderBy('name')->get(['id', 'name', 'company']),
            'articleSuggestions' => [],
        ]);
    }

    public function update(Request $request, Document $document, DocumentNumberGenerator $numbers): RedirectResponse
    {
        $this->authorize('update', $document);

        $data = $this->validated($request);

        DB::transaction(function () use ($data, $document, $numbers, $request): void {
            $wasDraft = $document->status === 'draft';
            $issuing = ($data['status'] === 'issued') && ($wasDraft || blank($document->number));

            $document->update([
                'client_id' => $data['client_id'] ?? null,
                'type' => $data['type'],
                'status' => $data['status'],
                'currency' => $data['currency'] ?? $document->currency,
                'title' => $data['title'] ?? null,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'issue_date' => $data['issue_date'] ?? $document->issue_date,
                'due_date' => $data['due_date'] ?? null,
                'tax_rate' => $data['tax_rate'] ?? 0,
                'number' => $issuing
                    ? ($document->number ?: $numbers->next($request->user()->currentTenant, $data['type']))
                    : $document->number,
                'issued_at' => $issuing ? now() : $document->issued_at,
            ]);

            $document->lines()->delete();
            $this->syncLines($document, $data['lines'] ?? []);
            $document->recalculateTotals();
        });

        ActivityLog::record('document.updated', $document, ['status' => $document->fresh()->status]);

        return redirect()
            ->route('app.documents.show', $document)
            ->with('success', 'Document mis à jour.');
    }

    public function convert(Request $request, Document $document, DocumentNumberGenerator $numbers): RedirectResponse
    {
        $this->authorize('convert', $document);

        $to = $request->validate([
            'to_type' => ['required', 'in:proforma,purchase_order,invoice'],
        ])['to_type'];

        $flow = [
            Document::TYPE_QUOTE => [Document::TYPE_PROFORMA, Document::TYPE_PURCHASE_ORDER, Document::TYPE_INVOICE],
            Document::TYPE_PROFORMA => [Document::TYPE_PURCHASE_ORDER, Document::TYPE_INVOICE],
            Document::TYPE_PURCHASE_ORDER => [Document::TYPE_INVOICE],
        ];

        abort_unless(in_array($to, $flow[$document->type] ?? [], true), 422, 'Conversion non autorisée.');

        $tenant = $request->user()->currentTenant;
        $document->load('lines');

        $new = DB::transaction(function () use ($document, $to, $numbers, $tenant, $request) {
            $new = Document::query()->create([
                'tenant_id' => $tenant->id,
                'client_id' => $document->client_id,
                'created_by' => $request->user()->id,
                'type' => $to,
                'number' => $numbers->next($tenant, $to),
                'status' => 'issued',
                'currency' => $document->currency,
                'title' => $document->title,
                'notes' => $document->notes,
                'terms' => $document->terms,
                'issue_date' => now()->toDateString(),
                'due_date' => $document->due_date,
                'tax_rate' => $document->tax_rate,
                'converted_from_id' => $document->id,
                'issued_at' => now(),
            ]);

            foreach ($document->lines as $i => $line) {
                $new->lines()->create([
                    'mercuriale_article_id' => $line->mercuriale_article_id,
                    'reference' => $line->reference,
                    'designation' => $line->designation,
                    'unit' => $line->unit,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'line_total' => $line->line_total,
                    'position' => $i,
                ]);
            }

            $document->update(['status' => 'converted']);
            $new->recalculateTotals();

            return $new;
        });

        ActivityLog::record('document.converted', $new, [
            'from' => $document->id,
            'to_type' => $to,
        ]);

        return redirect()
            ->route('app.documents.show', $new)
            ->with('success', 'Document converti.');
    }

    public function duplicate(Request $request, Document $document, PlanQuotaService $quotas): RedirectResponse
    {
        $this->authorize('duplicate', $document);

        $tenant = $request->user()->currentTenant;
        abort_unless($tenant, 403);

        try {
            $quotas->ensureDocumentQuota($tenant);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $document->load('lines');

        $copy = DB::transaction(function () use ($document, $tenant, $request, $quotas) {
            $copy = Document::query()->create([
                'tenant_id' => $tenant->id,
                'client_id' => $document->client_id,
                'created_by' => $request->user()->id,
                'type' => $document->type,
                'number' => null,
                'status' => 'draft',
                'currency' => $document->currency,
                'title' => $document->title ? $document->title.' (copie)' : 'Copie',
                'notes' => $document->notes,
                'terms' => $document->terms,
                'issue_date' => now()->toDateString(),
                'due_date' => $document->due_date,
                'tax_rate' => $document->tax_rate,
            ]);

            foreach ($document->lines as $i => $line) {
                $copy->lines()->create([
                    'mercuriale_article_id' => $line->mercuriale_article_id,
                    'reference' => $line->reference,
                    'designation' => $line->designation,
                    'unit' => $line->unit,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'line_total' => $line->line_total,
                    'position' => $i,
                ]);
            }

            $copy->recalculateTotals();
            $quotas->trackDocumentCreated($tenant);

            return $copy;
        });

        ActivityLog::record('document.duplicated', $copy, ['from' => $document->id]);

        return redirect()
            ->route('app.documents.show', $copy)
            ->with('success', 'Document duplique en brouillon.');
    }

    public function export(Request $request): StreamedResponse
    {
        $type = $request->get('type');
        $status = $request->get('status');

        $filename = 'documents-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($type, $status): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'type', 'number', 'status', 'client', 'total', 'currency', 'issue_date', 'created_at'], ';');

            Document::query()
                ->with('client:id,name')
                ->when($type, fn ($q) => $q->where('type', $type))
                ->when($status, fn ($q) => $q->where('status', $status))
                ->latest()
                ->chunk(200, function ($docs) use ($out): void {
                    foreach ($docs as $doc) {
                        fputcsv($out, [
                            $doc->id,
                            $doc->type,
                            $doc->number,
                            $doc->status,
                            $doc->client?->name,
                            $doc->total,
                            $doc->currency,
                            optional($doc->issue_date)?->format('Y-m-d'),
                            $doc->created_at?->toDateTimeString(),
                        ], ';');
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function searchArticles(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $articles = MercurialeArticle::query()
            ->where(function ($query) use ($q): void {
                $query->where('reference', 'like', "%{$q}%")
                    ->orWhere('designation', 'like', "%{$q}%");
            })
            ->limit(20)
            ->get(['id', 'reference', 'designation', 'unit', 'unit_price', 'quantity']);

        return response()->json($articles);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', 'in:quote,proforma,purchase_order,invoice'],
            'status' => ['required', 'in:draft,issued'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'currency' => ['nullable', 'string', 'max:8'],
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.mercuriale_article_id' => ['nullable', 'exists:mercuriale_articles,id'],
            'lines.*.reference' => ['nullable', 'string', 'max:20'],
            'lines.*.designation' => ['required', 'string', 'max:5000'],
            'lines.*.unit' => ['nullable', 'string', 'max:64'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit_price' => ['required', 'integer', 'min:0'],
        ]);

        if (($data['client_id'] ?? null) === '' || ($data['client_id'] ?? null) === null) {
            $data['client_id'] = null;
        }

        return $data;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function syncLines(Document $document, array $lines): void
    {
        foreach (array_values($lines) as $i => $line) {
            $qty = (float) $line['quantity'];
            $price = (int) $line['unit_price'];

            DocumentLine::query()->create([
                'document_id' => $document->id,
                'mercuriale_article_id' => $line['mercuriale_article_id'] ?? null,
                'reference' => $line['reference'] ?? null,
                'designation' => $line['designation'],
                'unit' => $line['unit'] ?? null,
                'quantity' => $qty,
                'unit_price' => $price,
                'line_total' => (int) round($qty * $price),
                'position' => $i,
            ]);
        }
    }
}
