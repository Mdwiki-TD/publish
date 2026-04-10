<?php

declare(strict_types=1);

namespace Publish\Tests;

use PHPUnit\Framework\TestCase;

class ProcessEditTest extends TestCase
{
    public function testNamespaceConstants(): void
    {
        $userNamespace = 2;
        $mainNamespace = 0;
        $categoryNamespace = 14;

        $this->assertSame(2, $userNamespace);
        $this->assertSame(0, $mainNamespace);
        $this->assertSame(14, $categoryNamespace);
    }

    public function testNamespaceComparison(): void
    {
        $ns = 2;
        $isUserPage = ($ns == 2);
        $this->assertTrue($isUserPage);
    }

    public function testNonUserNamespaceIsNotUserPage(): void
    {
        $ns = 0;
        $isUserPage = ($ns == 2);
        $this->assertFalse($isUserPage);
    }

    public static function setUpBeforeClass(): void {}
}
