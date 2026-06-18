@component('mail::message')
# Deine Mitfahrt wurde eingetragen

Hallo {{ $ride->name }},

dein Eintrag für die Veranstaltung **{{ $event->name }}** wurde erfolgreich gespeichert.

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
