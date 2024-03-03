<?php

namespace Lunar\Flutterwave\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Flutterwave\Tests\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }
}
