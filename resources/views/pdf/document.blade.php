<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $document->number ?? 'Document' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .header { margin-bottom: 24px; }
        .brand { font-size: 20px; font-weight: bold; color: #0b6e4f; }
        .muted { color: #555; }
        .meta { width: 100%; margin-bottom: 20px; }
        .meta td { vertical-align: top; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.lines th { background: #f3f6f4; text-align: left; padding: 8px; border-bottom: 1px solid #ddd; font-size: 11px; text-transform: uppercase; }
        table.lines td { padding: 8px; border-bottom: 1px solid #eee; vertical-align: top; }
        .right { text-align: right; }
        .totals { margin-top: 16px; width: 100%; }
        .totals td { padding: 4px 0; }
        .footer { margin-top: 40px; padding-top: 12px; border-top: 1px solid #ddd; font-size: 10px; color: #666; }
        .badge { display: inline-block; padding: 2px 8px; background: #e8f5ef; color: #0b6e4f; border-radius: 10px; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">{{ $tenant->name ?? 'Mercuria' }}</div>
        @if(!empty($tenant->letterhead))
            <div class="muted" style="white-space: pre-wrap; margin-top: 6px;">{{ $tenant->letterhead }}</div>
        @endif
        @if(!empty($tenant->email) || !empty($tenant->phone))
            <div class="muted" style="margin-top: 4px;">
                {{ $tenant->email }} @if($tenant->phone) · {{ $tenant->phone }} @endif
            </div>
        @endif
    </div>

    <table class="meta">
        <tr>
            <td style="width: 55%;">
                <strong>{{ $typeLabel }}</strong><br>
                <span class="badge">{{ strtoupper($document->status) }}</span><br>
                N° : <strong>{{ $document->number ?? 'BROUILLON' }}</strong><br>
                Date : {{ optional($document->issue_date)->format('d/m/Y') ?? '—' }}
                @if($document->due_date)
                    <br>Échéance : {{ $document->due_date->format('d/m/Y') }}
                @endif
            </td>
            <td style="width: 45%;">
                <strong>Client</strong><br>
                {{ $document->client->name ?? '—' }}<br>
                @if($document->client?->company){{ $document->client->company }}<br>@endif
                @if($document->client?->address)<span class="muted">{{ $document->client->address }}</span><br>@endif
                @if($document->client?->email){{ $document->client->email }}@endif
            </td>
        </tr>
    </table>

    @if($document->title)
        <p><strong>{{ $document->title }}</strong></p>
    @endif

    <table class="lines">
        <thead>
            <tr>
                <th style="width: 14%;">Réf.</th>
                <th>Désignation</th>
                <th style="width: 12%;">Qté</th>
                <th style="width: 16%;" class="right">P.U.</th>
                <th style="width: 16%;" class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document->lines as $line)
                <tr>
                    <td>{{ $line->reference }}</td>
                    <td>{{ $line->designation }}</td>
                    <td>{{ rtrim(rtrim(number_format((float) $line->quantity, 3, ',', ' '), '0'), ',') }} {{ $line->unit }}</td>
                    <td class="right">{{ number_format($line->unit_price, 0, ',', ' ') }}</td>
                    <td class="right">{{ number_format($line->line_total, 0, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="right muted">Sous-total</td>
            <td class="right" style="width: 160px;">{{ number_format($document->subtotal, 0, ',', ' ') }} {{ $document->currency }}</td>
        </tr>
        @if((float) $document->tax_rate > 0)
            <tr>
                <td class="right muted">TVA ({{ $document->tax_rate }}%)</td>
                <td class="right">{{ number_format($document->tax_amount, 0, ',', ' ') }} {{ $document->currency }}</td>
            </tr>
        @endif
        <tr>
            <td class="right"><strong>Total</strong></td>
            <td class="right"><strong>{{ number_format($document->total, 0, ',', ' ') }} {{ $document->currency }}</strong></td>
        </tr>
    </table>

    @if($document->notes)
        <p style="margin-top: 24px;"><strong>Notes</strong><br>{{ $document->notes }}</p>
    @endif
    @if($document->terms)
        <p style="margin-top: 12px;"><strong>Conditions</strong><br>{{ $document->terms }}</p>
    @endif

    <div class="footer">
        @if(!empty($tenant->footer_text))
            {{ $tenant->footer_text }}
        @else
            Document généré avec Mercuria — prix de référence MINCOMMERCE Budget 2026
        @endif
    </div>
</body>
</html>
