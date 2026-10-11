<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;

class DocumentShareController extends Controller
{
    public function store(Request $request, Document $document): JsonResponse
    {
        $this->authorize('send', $document);

        if (is_string($request->input('phone'))) {
            $phone = preg_replace('/[\s().-]/', '', $request->input('phone'));
            $request->merge(['phone' => preg_replace('/^(\+|00)/', '', $phone)]);
        }

        $data = $request->validate([
            'phone' => ['nullable', 'string', 'regex:/^[1-9][0-9]{7,14}$/'],
            'message' => ['nullable', 'string', 'max:2000'],
        ], [
            'phone.regex' => 'Indiquez un numéro international avec son indicatif, par exemple +237 6 12 34 56 78.',
            'phone.string' => 'Indiquez un numéro de téléphone valide.',
            'message.max' => 'Votre message ne doit pas dépasser 2 000 caractères.',
        ]);

        $document->load('client');
        $tenant = $document->tenant()->firstOrFail();
        $expiresAt = now()->addDays(7);
        $pdfUrl = $this->hasPublicHost($request->getHost())
            ? URL::temporarySignedRoute('documents.shared.pdf', $expiresAt, ['document' => $document->id])
            : null;
        $number = $document->number ?: '#'.$document->id;
        $total = number_format($document->total, 0, ',', ' ').' '.$document->currency;
        $message = 'Bonjour'.($document->client?->name ? ' '.$document->client->name : '').",\n\n";
        $message .= "Voici votre {$document->typeLabel()} {$number} de {$tenant->name}.\nTotal TTC : {$total}.";

        if ($document->ir_mode === 'withholding' && $document->ir_amount > 0) {
            $message .= "\nNet à payer après retenue IR : ".number_format($document->net_total, 0, ',', ' ').' '.$document->currency.'.';
        }

        if (! empty($data['message'])) {
            $message .= "\n\n".$data['message'];
        }

        $message .= $pdfUrl
            ? "\n\nTélécharger le PDF (lien valable 7 jours) :\n".$pdfUrl
            : "\n\nLe document PDF est joint à ce message.";
        $message .= "\n\nMerci de votre confiance.\n".$tenant->name;

        ActivityLog::record('document.whatsapp.prepared', $document);

        return response()->json([
            'whatsapp_url' => 'https://wa.me/'.($data['phone'] ?? '').'?'.http_build_query(['text' => $message], '', '&', PHP_QUERY_RFC3986),
            'message' => $message,
            'pdf_url' => $pdfUrl,
            'expires_at' => $pdfUrl ? $expiresAt->toIso8601String() : null,
            'requires_pdf_attachment' => $pdfUrl === null,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function show(string $document): Response
    {
        $sharedDocument = Document::withoutGlobalScope('tenant')->findOrFail($document);
        $tenant = $sharedDocument->tenant()->firstOrFail();
        abort_unless($tenant->is_active, 404);
        $sharedDocument->load([
            'lines',
            'client' => fn (BelongsTo $query) => $query->withoutGlobalScope('tenant')->where('tenant_id', $sharedDocument->tenant_id),
        ]);

        return Pdf::loadView('pdf.document', [
            'document' => $sharedDocument,
            'tenant' => $tenant,
            'typeLabel' => $sharedDocument->typeLabel(),
        ])->setPaper('a4')->download('document-'.$sharedDocument->id.'.pdf')
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    private function hasPublicHost(string $host): bool
    {
        $host = strtolower(trim($host, '[]'));
        if (! str_contains($host, '.') || preg_match('/\.(localhost|local|test|invalid)$/', $host)) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return (bool) filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        return true;
    }
}
