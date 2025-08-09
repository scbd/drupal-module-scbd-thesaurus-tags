<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../scbd_field.install';

final class ScbdFieldInstallHelpersTest extends TestCase {
  /**
   * @dataProvider csvProvider
   */
  public function testSplitCsv($input, array $expected): void {
    $this->assertSame($expected, _scbd_field_split_csv($input));
  }

  public static function csvProvider(): array {
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
