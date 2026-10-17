<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\FormulaTestCase;

class TruthinessTest extends FormulaTestCase
{
    public static function values(): array
    {
        return [
            'zero' => [0, false],
            'zero float' => [0.0, false],
            'a number' => [12.5, true],
            'empty string' => ['', false],
            'the string "0"' => ['0', false],
            'the string "false"' => ['false', false],
            'an identifier of zeros' => ['0000', true],
            'an identifier with leading zeros' => ['00000001', true],
            'a decimal string of zeros' => ['0.00', true],
            'text' => ['TID48213', true],
            'an empty list' => [[], false],
            'a list' => [[1], true],
        ];
    }

    #[DataProvider('values')]
    public function test_if_reads_a_value_as_true_or_false(mixed $value, bool $isTruthy)
    {
        $result = $this->evaluate('IF(code; "yes"; "no")', ['code' => $value]);

        $this->assertSame($isTruthy ? 'yes' : 'no', $result);
    }

    #[DataProvider('values')]
    public function test_and_and_or_read_a_value_as_if_does(mixed $value, bool $isTruthy)
    {
        $this->assertSame($isTruthy, $this->evaluate('code && "x"', ['code' => $value]));
        $this->assertSame($isTruthy, $this->evaluate('code || ""', ['code' => $value]));
    }

    public function test_filter_keeps_the_items_whose_field_reads_as_true()
    {
        $result = $this->evaluate('FILTER(items; code)', [
            'items' => [['code' => '0000'], ['code' => ''], ['code' => '0'], ['code' => '00000001']],
        ]);

        $this->assertEquals([0, 3], array_keys($result));
    }
}
