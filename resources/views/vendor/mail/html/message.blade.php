<x-mail::layout>
@php $orgName = \App\Models\Setting::instance()->organisation_name ?? 'Lift2Event'; @endphp
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ __('mail.header', ['org' => $orgName]) }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
{{ __('mail.footer') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
