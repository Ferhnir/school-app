<?php

namespace App\Mail;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TodayBookings extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $parent,
        public readonly Carbon $date,
        public readonly Collection $bookings,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your bookings for ' . $this->date->format('l, d F Y'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.today-bookings');
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('pdf.parent-today', [
            'parent'   => $this->parent,
            'date'     => $this->date,
            'bookings' => $this->bookings,
        ])->setPaper('a4');

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                'my-bookings-' . $this->date->toDateString() . '.pdf'
            )->withMime('application/pdf'),
        ];
    }
}
