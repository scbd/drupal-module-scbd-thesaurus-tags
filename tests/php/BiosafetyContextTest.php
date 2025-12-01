<?php

namespace Drupal\Tests\scbd_field;

use Drupal\scbd_field\Utility\BiosafetyContext;
use PHPUnit\Framework\TestCase;

// Ensure the BiosafetyContext class is available when Composer autoload
// metadata has not yet been regenerated in local development.
// phpcs:ignore PSR1.Files.SideEffects.FoundWithSymbols
require_once __DIR__ . '/../../src/Utility/BiosafetyContext.php';

/**
 * @coversDefaultClass \Drupal\scbd_field\Utility\BiosafetyContext
 */
class BiosafetyContextTest extends TestCase
{
    /**
     * @covers ::isBiosafetyContext
     */
    public function testReturnsTrueWhenGlobalFlagIsTrue(): void
    {
        $this->assertTrue(BiosafetyContext::isBiosafetyContext(true, []));
    }

    /**
     * @covers ::isBiosafetyContext
     */
    public function testReturnsTrueWhenBiosafetyDomainsPresent(): void
    {
        $this->assertTrue(BiosafetyContext::isBiosafetyContext(false, ['bchSubjects']));
        $this->assertTrue(BiosafetyContext::isBiosafetyContext(false, ['subjects', 'bchSubjectGroups']));
    }

    /**
     * @covers ::isBiosafetyContext
     */
    public function testReturnsFalseWhenNoFlagOrDomains(): void
    {
        $this->assertFalse(BiosafetyContext::isBiosafetyContext(false, ['subjects', 'countries']));
        $this->assertFalse(BiosafetyContext::isBiosafetyContext(false, []));
    }
}
