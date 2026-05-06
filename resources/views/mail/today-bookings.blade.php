@component('mail::message')
# Your bookings for {{ $date->format('l, d F Y') }}

Hi **{{ $parent->name }}**, here is a summary of your appointments today.

@component('mail::table')
| Teacher | Time |
|:--------|:-----|
@foreach ($bookings as $booking)
| {{ $booking['teacher'] }} | {{ $booking['time'] }} |
@endforeach
@endcomponent

@if ($bookings->isEmpty())
You have no bookings scheduled for today.
@endif

Thanks,
{{ config('app.name') }}
@endcomponent
