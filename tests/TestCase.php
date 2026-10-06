<?php

namespace Tests;

use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Templates are required by TemplatedMailService; seed them so any
        // test that triggers an email (OTP, status change, etc.) resolves
        // its template without a RuntimeException.
        if (property_exists($this, 'seedEmailTemplates') === false || $this->seedEmailTemplates !== false) {
            $this->seed(EmailTemplateSeeder::class);
        }
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
