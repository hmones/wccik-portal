<?php

namespace Tests\Unit;

use App\Services\Mail\TemplatedMailService;
use PHPUnit\Framework\TestCase;

class TemplatedMailServiceTest extends TestCase
{
    public function test_substitutes_simple_tokens(): void
    {
        $this->assertEquals(
            'Hello Ayesha, your code is 123456.',
            TemplatedMailService::interpolate(
                'Hello {{ name }}, your code is {{ code }}.',
                ['name' => 'Ayesha', 'code' => '123456'],
            ),
        );
    }

    public function test_handles_tokens_without_spaces(): void
    {
        $this->assertEquals(
            'Order #42 for Jane',
            TemplatedMailService::interpolate(
                'Order #{{order_id}} for {{name}}',
                ['order_id' => 42, 'name' => 'Jane'],
            ),
        );
    }

    public function test_leaves_unknown_tokens_untouched(): void
    {
        $this->assertEquals(
            'Hi {{ unknown }}, your name is Jane',
            TemplatedMailService::interpolate(
                'Hi {{ unknown }}, your name is {{ name }}',
                ['name' => 'Jane'],
            ),
        );
    }

    public function test_null_values_become_empty_string(): void
    {
        $this->assertEquals(
            'Reason: ',
            TemplatedMailService::interpolate('Reason: {{ reason }}', ['reason' => null]),
        );
    }
}
