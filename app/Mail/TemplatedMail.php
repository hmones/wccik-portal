<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Generic mailable that renders a subject + markdown body already resolved
 * from the email_templates table by TemplatedMailService.
 *
 * @property array<int, array{data: string, filename: string, mime?: string}> $resolvedAttachments
 */
class TemplatedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{data: string, filename: string, mime?: string}>  $resolvedAttachments
     */
    public function __construct(
        public string $resolvedSubject,
        public string $resolvedBody,
        public array $resolvedAttachments = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->resolvedSubject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.templated',
            with: [
                'body' => $this->resolvedBody,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return collect($this->resolvedAttachments)
            ->map(fn (array $a) => Attachment::fromData(fn () => $a['data'], $a['filename'])
                ->withMime($a['mime'] ?? 'application/octet-stream'))
            ->all();
    }
}
