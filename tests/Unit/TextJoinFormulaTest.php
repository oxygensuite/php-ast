<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\FormulaTestCase;

class TextJoinFormulaTest extends FormulaTestCase
{
    private const string ADDRESS = 'TEXTJOIN(", "; TEXTJOIN(" "; a.street; a.number); TEXTJOIN(" "; a.postal_code; a.city))';

    public static function addresses(): array
    {
        return [
            'everything' => [['street' => 'ΟΔΟΣ ΕΡΜΟΥ', 'number' => '12', 'postal_code' => '10563', 'city' => 'ΑΘΗΝΑ'], 'ΟΔΟΣ ΕΡΜΟΥ 12, 10563 ΑΘΗΝΑ'],
            'no number' => [['street' => 'ΟΔΟΣ ΕΡΜΟΥ', 'postal_code' => '10563', 'city' => 'ΑΘΗΝΑ'], 'ΟΔΟΣ ΕΡΜΟΥ, 10563 ΑΘΗΝΑ'],
            'no street or number' => [['postal_code' => '10563', 'city' => 'ΑΘΗΝΑ'], '10563 ΑΘΗΝΑ'],
            'empty and blank text' => [['street' => ' ', 'number' => '', 'postal_code' => '10563', 'city' => '	'], '10563'],
            'nothing' => [[], ''],
        ];
    }

    #[DataProvider('addresses')]
    public function test_textjoin_joins_the_values_that_are_there(array $address, string $expected)
    {
        $this->assertSame($expected, $this->evaluate(self::ADDRESS, ['a' => $address]));
    }

    public function test_textjoin_joins_numbers_and_keeps_zero()
    {
        $this->assertSame('ΑΠΥ-0-42', $this->evaluate('TEXTJOIN("-"; header.series; 0; 42)'));
    }

    public function test_textjoin_joins_the_items_of_a_list()
    {
        $this->assertSame('P-001, P-002, P-003, P-004, P-005', $this->evaluate('TEXTJOIN(", "; lines.*.item_code)'));
    }

    public function test_textjoin_joins_with_a_missing_or_numeric_separator()
    {
        $this->assertSame('ab', $this->evaluate('TEXTJOIN(header.missing; "a"; "b")'));
        $this->assertSame('a0b', $this->evaluate('TEXTJOIN(0; "a"; "b")'));
    }

    public static function listArguments(): array
    {
        return [
            'a list of records to join' => ['TEXTJOIN(", "; "a"; lines)', 'TEXTJOIN() joins text and numbers; argument 3 holds a list or a record'],
            'a list as the separator' => ['TEXTJOIN(lines.*.item_code; "a"; "b")', 'TEXTJOIN() joins text and numbers; argument 1 holds a list or a record'],
        ];
    }

    #[DataProvider('listArguments')]
    public function test_textjoin_rejects_a_list_where_it_joins_text(string $formula, string $message)
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        $this->evaluate($formula);
    }
}
