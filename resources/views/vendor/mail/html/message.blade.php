<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
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
© {{ date('Y') }} Women Chamber of Commerce &amp; Industry, Karachi. All rights reserved.<br>
<small>A/10, 1st Floor, Cause Way Apartment, Mehran Town, Korangi, Karachi &middot; info@wccik.org.pk</small>
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
