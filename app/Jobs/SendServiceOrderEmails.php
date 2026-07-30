<?php

namespace App\Jobs;

use App\Mail\ServiceOrderToCustomer;
use App\Mail\ServiceOrderToPerformer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendServiceOrderEmails implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{title: string, url: string}  $service
     * @param  array{name: string, email: string}  $performer
     * @param  array{name: string, email: string, phone: ?string, message: string, options: array<int, string>}  $customer
     */
    public function __construct(
        public readonly array $service,
        public readonly array $performer,
        public readonly array $customer,
    ) {}

    public function handle(): void
    {
        Mail::to($this->performer['email'])
            ->send(new ServiceOrderToPerformer($this->service, $this->performer, $this->customer));

        Mail::to($this->customer['email'])
            ->send(new ServiceOrderToCustomer($this->service, $this->performer, $this->customer));
    }
}
