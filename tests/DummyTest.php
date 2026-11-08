<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class DummyTest extends TestCase
{
    /**
     * @test
     */
    public function testFakeAssertTrue(): void
    {
        $this->assertEquals(1,1);
    }
}