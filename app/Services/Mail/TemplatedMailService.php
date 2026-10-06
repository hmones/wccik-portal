<?php

namespace App\Services\Mail;

use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Dispatches templated email. Lookup is by `key`; template content is pulled
 * from the DB so admins can edit copy via Nova without a deploy. Variable
 * substitution is deliberately simple: `{{ variable_name }}` tokens (with
 * flexible whitespace) are replaced by the matching value in `$variables`.
 * No Blade evaluation of admin-authored content — keeps the surface small.
 */
class TemplatedMailService
{
    /**
     * @param  array<string, scalar|null>  $variables
     * @param  array<int, array{data: string, filename: string, mime?: string}>  $attachments
     */
    public function send(
        string $key,
        string $to,
        array $variables = [],
        ?string $locale = null,
        array $attachments = [],
    ): void {
        $template = EmailTemplate::where('key', $key)->first();

        if ($template === null) {
            throw new RuntimeException("Email template [{$key}] not found. Did you run the seeder?");
        }

        $locale ??= app()->getLocale();
        $subject = self::interpolate($template->subjectFor($locale), $variables);
        $body = self::interpolate($template->bodyFor($locale), $variables);

        Mail::to($to)->send(new TemplatedMail($subject, $body, $attachments));
    }

    /**
     * @param  array<string, scalar|null>  $variables
     */
    public static function interpolate(string $template, array $variables): string
    {
        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/',
            function (array $m) use ($variables): string {
                $name = $m[1];
                if (! array_key_exists($name, $variables)) {
                    return $m[0];
                }

                return (string) ($variables[$name] ?? '');
            },
            $template,
        ) ?? $template;
    }
}
