<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\FormulaTestCase;

class EmptyFormulaTest extends FormulaTestCase
{
    public static function values(): array
    {
        return [
            'a missing value' => ['header.missing', true],
            'an empty string' => ['""', true],
            'blank text' => ['" "', true],
            'an empty list' => ['FILTER(lines; quantity > 100)', true],
            'zero' => ['0', false],
            'a zero as text' => ['"0"', false],
            'a text' => ['header.series', false],
            'a list' => ['lines', false],
        ];
    }

    #[DataProvider('values')]
    public function test_empty_tells_whether_the_value_is_not_there(string $value, bool $isEmpty)
    {
        $this->assertSame($isEmpty, $this->evaluate("EMPTY($value)"));
    }

    #[DataProvider('values')]
    public function test_exists_is_the_opposite_of_empty(string $value, bool $isEmpty)
    {
        $this->assertSame(! $isEmpty, $this->evaluate("EXISTS($value)"));
    }
}
