@component('mail::message')
# {{ $ride->type === 'offer' ? __('mail.heading_offer') : __('mail.heading_request') }}

{{ __('mail.greeting', ['name' => $ride->name]) }}

{{ __('mail.saved', ['event' => $event->name]) }}

@if (! $ride->confirmed_at)
{{ __('mail.confirm_prompt') }}

@component('mail::button', ['url' => $confirmUrl, 'color' => 'success'])
{{ $ride->type === 'offer' ? __('mail.confirm_offer') : __('mail.confirm_request') }}
@endcomponent

{{ __('mail.confirm_direct', ['url' => $confirmUrl]) }}
@endif

{{ __('mail.manage_prompt') }}

@component('mail::button', ['url' => $editUrl, 'color' => 'success'])
{{ __('mail.edit_button') }}
@endcomponent

@component('mail::button', ['url' => $deleteUrl, 'color' => 'error'])
{{ __('mail.delete_button') }}
@endcomponent

@if ($bookUrl && ! $ride->is_booked)
@component('mail::button', ['url' => $bookUrl, 'color' => 'orange'])
{{ __('mail.book_button') }}
@endcomponent
@endif

{{ __('mail.direct_links') }}

{{ __('mail.edit_direct', ['url' => $editUrl]) }}

{{ __('mail.delete_direct', ['url' => $deleteUrl]) }}

@if ($bookUrl && ! $ride->is_booked)
{{ __('mail.book_direct', ['url' => $bookUrl]) }}
@endif

{{ __('mail.signoff') }},
{{ $organisationName }}
@endcomponent
