<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\FormulaTestCase;

class LenFormulaTest extends FormulaTestCase
{
    public static function values(): array
    {
        return [
            'a text, counting characters rather than bytes' => ['header.series', 3],
            'a number written out' => ['summary.total_net_amount', 5],
            'an empty string' => ['""', 0],
            'a missing value' => ['header.missing', 0],
        ];
    }

    #[DataProvider('values')]
    public function test_len_counts_the_characters(string $value, int $expected)
    {
        $this->assertSame($expected, $this->evaluate("LEN($value)"));
    }

    public function test_len_rejects_a_list()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('LEN() measures text and numbers; COUNT() counts the items of a list or a record');

        $this->evaluate('LEN(lines)');
    }
}
