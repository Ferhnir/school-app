<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class BookingCancelled extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $teacher,
        public readonly Carbon $date,
        public readonly string $time,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your booking has been cancelled');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.booking-cancelled');
    }
}
