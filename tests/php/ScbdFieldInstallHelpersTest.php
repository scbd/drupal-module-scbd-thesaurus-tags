<?php

namespace Drupal\scbd_field\Tests;

use PHPUnit\Framework\TestCase;

final class ScbdFieldInstallHelpersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!function_exists('_scbd_field_split_csv')) {
            require_once __DIR__ . '/../scbd_field.install';
        }
    }
    /**
     * @dataProvider csvProvider
     */
    public function testSplitCsv($input, array $expected): void
    {
        $this->assertSame($expected, _scbd_field_split_csv($input));
    }

    public static function csvProvider(): array
    {
        return [
            [null, []],
            ['', []],
            ['  ', []],
            ['one', ['one']],
            [' one ', ['one']],
            ['one,two', ['one', 'two']],
            ['one, two , three ', ['one', 'two', 'three']],
            [',,one,,two,', ['one', 'two']],
        ];
    }
}
