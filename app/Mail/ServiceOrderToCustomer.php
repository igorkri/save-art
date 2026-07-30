<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ServiceOrderToCustomer extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{title: string, url: string}  $service
     * @param  array{name: string}  $performer
     * @param  array{name: string, email: string, phone: ?string, message: string, options: array<int, string>}  $customer
     */
    public function __construct(
        public readonly array $service,
        public readonly array $performer,
        public readonly array $customer,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ваш запит надіслано: '.$this->service['title'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.service-order-to-customer',
            with: [
                'service' => $this->service,
                'performer' => $this->performer,
                'customer' => $this->customer,
            ],
        );
    }
}
