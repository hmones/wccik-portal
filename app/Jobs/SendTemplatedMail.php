<?php

namespace App\Jobs;

use App\Services\Mail\TemplatedMailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendTemplatedMail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @param array<string, scalar|null> $variables */
    public function __construct(
        public string $key,
        public string $to,
        public array $variables,
        public string $locale,
    ) {}

    public function handle(TemplatedMailService $mailer): void
    {
        $mailer->send($this->key, $this->to, $this->variables, $this->locale);
    }
}
