<x-mail::message>
# {{ $typeLabel }} de {{ $tenant->name }}

Bonjour{{ $document->client?->name ? ' '.$document->client->name : '' }},

Veuillez trouver ci-joint votre **{{ strtolower($typeLabel) }}**
@if($document->number)
n° **{{ $document->number }}**
@endif
d'un montant total de **{{ number_format($document->total, 0, ',', ' ') }} {{ $document->currency }}**.

@if($customMessage)
{{ $customMessage }}
@endif

Merci de votre confiance.<br>
{{ $tenant->name }}
@if($tenant->phone)
<br>{{ $tenant->phone }}
@endif
@if($tenant->email)
<br>{{ $tenant->email }}
@endif

<x-mail::subcopy>
Document généré avec Mercuria — mercuriale MINCOMMERCE Budget 2026.
</x-mail::subcopy>
</x-mail::message>
