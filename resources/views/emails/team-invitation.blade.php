<x-mail::message>
# Invitation a rejoindre {{ $tenantName }}

Vous etes invite(e) a collaborer sur **Mercuria** en tant que **{{ $role }}**.

<x-mail::button :url="$acceptUrl">
Accepter l'invitation
</x-mail::button>

Ce lien expire le {{ $invitation->expires_at->format('d/m/Y H:i') }}.

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
