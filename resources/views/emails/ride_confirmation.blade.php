@component('mail::message')
# Deine Mitfahrt wurde eingetragen

Hallo {{ $ride->name }},

dein Eintrag für die Veranstaltung **{{ $event->name }}** wurde erfolgreich gespeichert.

@if (! $ride->confirmed_at)
Damit er für andere Besucher sichtbar wird, bestätige ihn bitte über diesen Link:

@component('mail::button', ['url' => $confirmUrl, 'color' => 'success'])
Mitfahrt bestätigen
@endcomponent

**Bestätigen:** {{ $confirmUrl }}
@endif

Über die folgenden Links kannst du deinen Eintrag jederzeit bearbeiten oder löschen:

@component('mail::button', ['url' => $editUrl, 'color' => 'success'])
Eintrag bearbeiten
@endcomponent

@component('mail::button', ['url' => $deleteUrl, 'color' => 'error'])
Eintrag löschen
@endcomponent

Oder verwende diese Links direkt:

**Bearbeiten:** {{ $editUrl }}

**Löschen:** {{ $deleteUrl }}

Viele Grüße,
{{ config('app.name') }}
@endcomponent
