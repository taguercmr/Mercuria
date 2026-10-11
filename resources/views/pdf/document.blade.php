<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $document->number ?? $typeLabel }}</title>
    @php
        $designs = config('documents.designs');
        $designKey = array_key_exists($document->design ?? '', $designs) ? $document->design : 'signature';
        $design = $designs[$designKey];
        $statusLabels = ['draft' => 'Brouillon', 'issued' => 'Émis', 'sent' => 'Envoyé', 'converted' => 'Converti', 'cancelled' => 'Annulé', 'paid' => 'Payé'];
        $formatMoney = fn ($amount) => number_format((float) $amount, 0, ',', ' ').' '.$document->currency;
        $formatRate = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', ''), '0'), ',');
        $logoPath = $tenant?->logo_path ? Illuminate\Support\Facades\Storage::disk('public')->path($tenant->logo_path) : null;
        $signaturePath = $tenant?->signature_path ? Illuminate\Support\Facades\Storage::disk('public')->path($tenant->signature_path) : null;
        $initials = implode('', array_map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)), array_slice(explode(' ', $tenant->name ?? 'Mercuria'), 0, 2)));
    @endphp
    <style>
        @page { margin: 35px 35px 48px; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #25372f; line-height: 1.35; }
        .header { background: {{ $design['paper'] }}; border-top: 5px solid {{ $design['accent'] }}; padding: 18px; margin-bottom: 18px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .issuer { width: 55%; vertical-align: top; text-align: left; }
        .monogram { display: inline-block; padding: 8px 11px; font-size: 15px; font-weight: bold; background: {{ $design['accent'] }}; color: #fff; margin-bottom: 9px; border-radius: 5px 0; }
        .logo { max-width: 110px; max-height: 55px; margin-bottom: 9px; }
        .brand { font-size: 18px; font-weight: bold; color: {{ $design['accent'] }}; }
        .contact { font-size: 8px; color: #6c7e73; margin-top: 5px; word-wrap: break-word; }
        .doc-info { width: 45%; text-align: right; vertical-align: top; }
        .kicker { color: #75857b; font-size: 7px; letter-spacing: 1px; text-transform: uppercase; }
        .doc-title { font-size: 26px; font-weight: bold; color: {{ $design['accent'] }}; margin: 6px 0; line-height: 1.25; }
        .status { display: inline-block; border: 1px solid #b8c9bc; padding: 1px 5px; font-size: 8px; margin-left: 5px; }
        .letterhead { white-space: pre-wrap; color: #65796c; font-size: 9px; margin: 0 0 20px; }
        .details { width: 100%; border-collapse: collapse; margin: 0 0 16px; }
        .details td { vertical-align: top; }
        .client-name { font-size: 14px; color: {{ $design['accent'] }}; margin: 5px 0; font-weight: bold; }
        .client-details { font-size: 9px; color: #6b7d71; }
        .dates { width: 100%; border-collapse: collapse; background: {{ $design['paper'] }}; }
        .dates td { padding: 7px 10px; font-size: 8px; }
        .subject { text-align: left; font-size: 12px; font-weight: bold; margin: 0 0 14px; }
        .lines { width: 100%; table-layout: fixed; border-collapse: collapse; }
        .lines th { background: {{ $design['accent'] }}; color: #fff; padding: 10px 8px; font-size: 8px; font-weight: bold; text-align: right; }
        .lines td { padding: 9px 8px; border-bottom: 1px solid #e0e7e1; vertical-align: top; font-size: 9px; text-align: right; word-wrap: break-word; }
        .lines tr { page-break-inside: avoid; }
        .lines .description { text-align: left; line-height: 1.65; }
        .lines tbody tr:nth-child(even) { background: #f8faf8; }
        .reference { display: block; color: #75887c; font-size: 8px; font-family: DejaVu Sans Mono, monospace; margin-bottom: 3px; }
        .unit { display: block; font-size: 7px; color: #7d8a80; }
        .totals { margin: 18px 0 0 auto; width: 53%; border-collapse: collapse; page-break-inside: avoid; }
        .totals td { padding: 5px 8px; font-size: 9px; }
        .totals .amount { text-align: right; white-space: nowrap; font-weight: bold; }
        .totals .ttc td { border-top: 1px solid #b9cabc; font-size: 13px; font-weight: bold; color: {{ $design['accent'] }}; padding-top: 12px; }
        .totals .net td { color: #fff; background: {{ $design['accent'] }}; font-weight: bold; font-size: 11px; padding: 10px 8px; }
        .notes { margin-top: 12px; text-align: left; font-size: 9px; color: #64766a; white-space: pre-wrap; }
        .notes h4 { font-size: 9px; color: {{ $design['accent'] }}; margin: 0 0 7px; }
        .signature { text-align: right; margin-top: 20px; font-size: 8px; color: #76857a; }
        .signature img { max-width: 130px; max-height: 70px; }
        .footer { margin-top: 18px; text-align: left; padding-top: 12px; border-top: 1px solid #dce5dd; font-size: 8px; color: #7e8c81; white-space: pre-wrap; }
        .page-footer { position: fixed; bottom: -30px; left: 0; right: 0; color: #93a095; font-size: 7px; }
        .page-number { float: right; }.page-number:after { content: counter(page); }
        .executive .header { background: #172e37; border-color: #bd9961; }.executive .brand, .executive .doc-title { color: white; }.executive .header .contact { color: #b9cec5; }.executive .header .kicker { color: #c7ab81; }.executive .monogram { background: #bd9961; color: #172e37; }
        .minimal .header { background: white; border: none; padding: 15px 0; }.minimal .monogram { background: white; color: #253e35; border: 1px solid #c4d2c8; }.minimal .doc-title { font-weight: normal; }.minimal .lines th { background: white; color: #253e35; border-top: 1px solid #253e35; border-bottom: 1px solid #253e35; }.minimal .lines tbody tr { background: white; }.minimal .dates { background: white; }
        .classic .header { background: white; border-top: 0; border-bottom: 3px double #7e897b; padding: 15px 0 20px; }.classic .issuer, .classic .doc-info { width: 100%; text-align: center; }.classic .doc-info { padding-top: 12px; }.classic .brand, .classic .doc-title { font-family: DejaVu Serif, serif; }.classic .doc-title { text-transform: uppercase; letter-spacing: 2px; font-size: 23px; }.classic .doc-info .kicker { display: none; }.classic .lines th { background: #eeede6; color: #404d3e; border: 1px solid #cfd6cb; }.classic .lines td { border: 1px solid #cfd6cb; }
        .atelier .header { border: none; border-left: 7px solid #995637; background: #fbf2e9; }.atelier .doc-title { font-family: DejaVu Serif, serif; font-style: italic; }.atelier .monogram { background: transparent; color: #995637; border: 1px solid #995637; border-radius: 20px; }.atelier .lines th { background: #f2e5d7; color: #7c4a30; }.atelier .lines tbody tr:nth-child(even) { background: #fcf6ed; }
    </style>
</head>
<body class="{{ $designKey }}">
    <div class="page-footer">Créé avec Mercuria <span class="page-number">Page </span></div>
    <div class="header"><table class="header-table"><tr>
        <td class="issuer" @if($designKey === 'classic') colspan="2" @endif>
            @if($logoPath && is_file($logoPath))<img class="logo" src="{{ str_replace('\\', '/', $logoPath) }}" alt="Logo"><br>@else<span class="monogram">{{ $initials }}</span><br>@endif
            <div class="brand">{{ $tenant->name ?? 'Mercuria' }}</div>
            <div class="contact">{{ $tenant->email ?? '' }}@if($tenant?->phone) · {{ $tenant->phone }}@endif</div>
        </td>
        @if($designKey === 'classic')</tr><tr>@endif
        <td class="doc-info" @if($designKey === 'classic') colspan="2" @endif><span class="kicker">Document commercial</span><div class="doc-title">{{ $typeLabel }}</div><div class="contact">{{ $document->number ?? 'Sans numéro' }} <span class="status">{{ $statusLabels[$document->status] ?? $document->status }}</span></div></td>
    </tr></table></div>
    @if($tenant?->letterhead)<p class="letterhead">{{ $tenant->letterhead }}</p>@endif
    <table class="details"><tr><td style="width: 57%; padding-right: 20px;"><span class="kicker">Préparé pour</span><div class="client-name">{{ $document->client?->name ?? 'Client à renseigner' }}</div><div class="client-details">@if($document->client?->company){{ $document->client->company }}<br>@endif @if($document->client?->address){{ $document->client->address }}<br>@endif @if($document->client?->email){{ $document->client->email }}<br>@endif @if($document->client?->tax_id)NIU : {{ $document->client->tax_id }}@endif</div></td><td style="width: 43%;"><table class="dates"><tr><td>Date d’émission</td><td style="text-align: right;">{{ $document->issue_date?->format('d/m/Y') ?? '—' }}</td></tr>@if($document->due_date)<tr><td>Échéance</td><td style="text-align: right;">{{ $document->due_date->format('d/m/Y') }}</td></tr>@endif<tr><td>Devise</td><td style="text-align: right;">{{ $document->currency }}</td></tr></table></td></tr></table>
    @if($document->title)<p class="subject">{{ $document->title }}</p>@endif
    <table class="lines"><thead><tr><th style="width: 48%; text-align: left;">Référence / Désignation</th><th style="width: 12%;">Quantité</th><th style="width: 19%;">P.U. HT</th><th style="width: 21%;">Montant HT</th></tr></thead><tbody>
    @foreach($document->lines as $line)
        <tr><td class="description">@if($line->reference)<span class="reference">{{ $line->reference }}</span>@endif{{ $line->designation }}</td><td>{{ rtrim(rtrim(number_format((float) $line->quantity, 3, ',', ' '), '0'), ',') }}<span class="unit">{{ $line->unit }}</span></td><td>{{ number_format($line->unit_price, 0, ',', ' ') }}</td><td>{{ number_format($line->line_total, 0, ',', ' ') }}</td></tr>
    @endforeach
    </tbody></table>
    <table class="totals"><tr><td>Total HT</td><td class="amount">{{ $formatMoney($document->subtotal) }}</td></tr><tr><td>TVA ({{ $formatRate($document->tax_rate) }} %)</td><td class="amount">{{ $formatMoney($document->tax_amount) }}</td></tr>
    @if($document->ir_mode === 'addition' && (float) $document->ir_rate > 0)<tr><td>IR ajouté ({{ $formatRate($document->ir_rate) }} % du HT)</td><td class="amount">{{ $formatMoney($document->ir_amount) }}</td></tr>@endif
    <tr class="ttc"><td>Total TTC</td><td class="amount">{{ $formatMoney($document->total) }}</td></tr>
    @if($document->ir_mode === 'withholding' && (float) $document->ir_rate > 0)<tr><td>Retenue IR ({{ $formatRate($document->ir_rate) }} % du HT)</td><td class="amount">- {{ $formatMoney($document->ir_amount) }}</td></tr><tr class="net"><td>Net à payer</td><td class="amount">{{ $formatMoney($document->net_total) }}</td></tr>@endif
    </table>
    @if($document->notes)<div class="notes"><h4>À NOTER</h4>{{ $document->notes }}</div>@endif
    @if($document->terms)<div class="notes"><h4>CONDITIONS</h4>{{ $document->terms }}</div>@endif
    @if($signaturePath && is_file($signaturePath))<div class="signature">Signature<br><img src="{{ str_replace('\\', '/', $signaturePath) }}" alt="Signature"></div>@endif
    <div class="footer">{{ $tenant->footer_text ?: 'Merci pour votre confiance.' }}</div>
</body>
</html>
