<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentTemplateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('App/Templates/Index', [
            'templates' => DocumentTemplate::query()
                ->latest()
                ->get(),
            'types' => Document::TYPES,
        ]);
    }

    public function storeFromDocument(Request $request, Document $document): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $document->load('lines');

        DocumentTemplate::query()->create([
            'tenant_id' => $request->user()->current_tenant_id,
            'created_by' => $request->user()->id,
            'name' => $data['name'],
            'type' => $document->type,
            'title' => $document->title,
            'notes' => $document->notes,
            'terms' => $document->terms,
            'tax_rate' => $document->tax_rate,
            'lines' => $document->lines->map(fn ($l) => [
                'mercuriale_article_id' => $l->mercuriale_article_id,
                'reference' => $l->reference,
                'designation' => $l->designation,
                'unit' => $l->unit,
                'quantity' => (float) $l->quantity,
                'unit_price' => (int) $l->unit_price,
            ])->values()->all(),
        ]);

        return back()->with('success', 'Modele enregistre. Retrouvez-le dans Modeles.');
    }

    public function destroy(DocumentTemplate $template): RedirectResponse
    {
        $template->delete();

        return back()->with('success', 'Modele supprime.');
    }
}
