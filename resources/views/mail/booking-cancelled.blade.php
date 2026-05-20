@component('mail::message')
# Your Booking Has Been Cancelled

We're sorry to let you know that your appointment with **{{ $teacher->name }}** scheduled for **{{ $date->format('l, d F Y') }}** at **{{ $time }}** has been cancelled.

Please contact the school to arrange a new appointment if needed.

Thanks,
{{ config('app.name') }}
@endcomponent
