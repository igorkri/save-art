<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{name: string, email: string, phone: ?string, about: ?string}  $applicant
     * @param  array{path: string, original_name: string}|null  $resume  Тимчасовий шлях (storage/app) до резюме, якщо додано
     */
    public function __construct(
        public readonly array $applicant,
        public readonly ?array $resume = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Нова заявка на співпрацю: '.$this->applicant['name'],
            replyTo: $this->applicant['email'] ? [$this->applicant['email']] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.application-submitted',
            with: [
                'applicant' => $this->applicant,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! $this->resume) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('local', $this->resume['path'])
                ->as($this->resume['original_name']),
        ];
    }
}
