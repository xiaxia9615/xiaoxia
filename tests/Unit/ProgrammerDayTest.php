<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ProgrammerDayTest extends TestCase
{
    public function test_1024_equals_two_to_the_tenth(): void
    {
        $this->assertSame(1024, 2 ** 10);
        $this->assertSame('10000000000', decbin(1024));
    }
}
