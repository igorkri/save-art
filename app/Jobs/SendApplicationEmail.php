<?php

namespace App\Jobs;

use App\Mail\ApplicationSubmitted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendApplicationEmail implements ShouldQueue
{
    use Queueable;

    // Без повторних спроб: якщо ретраїти, тимчасовий файл резюме (видаляється одразу після
    // першої спроби нижче) буде відсутній для наступної спроби вкладення.
    public int $tries = 1;

    /**
     * @param  array{name: string, email: string, phone: ?string, about: ?string}  $applicant
     * @param  array{path: string, original_name: string}|null  $resume  Тимчасовий файл на диску 'local'
     */
    public function __construct(
        public readonly array $applicant,
        public readonly ?array $resume = null,
    ) {}

    public function handle(): void
    {
        try {
            Mail::to(config('services.application_email'))
                ->send(new ApplicationSubmitted($this->applicant, $this->resume));
        } finally {
            if ($this->resume) {
                Storage::disk('local')->delete($this->resume['path']);
            }
        }
    }
}
