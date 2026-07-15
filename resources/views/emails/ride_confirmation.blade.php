@component('mail::message')
# {{ $ride->type === 'offer' ? __('mail.heading_offer') : __('mail.heading_request') }}

Hallo {{ $ride->name }},

dein Eintrag für die Veranstaltung **{{ $event->name }}** wurde erfolgreich gespeichert.

@if (! $ride->confirmed_at)
Damit er für andere Besucher sichtbar wird, bestätige ihn bitte über diesen Link:

@component('mail::button', ['url' => $confirmUrl, 'color' => 'success'])
{{ $ride->type === 'offer' ? __('mail.confirm_offer') : __('mail.confirm_request') }}
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
{{ \App\Models\Setting::instance()->organisation_name ?? 'Lift2Event' }}
@endcomponent
