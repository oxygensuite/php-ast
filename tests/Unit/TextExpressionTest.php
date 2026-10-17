<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\FormulaTestCase;

class TextExpressionTest extends FormulaTestCase
{
    public static function expressions(): array
    {
        return [
            'text or text' => ['address.street || address.city', true],
            'empty text or text' => ['address.empty || address.city', true],
            'missing value or text' => ['address.missing || address.city', true],
            'empty text or missing value' => ['address.empty || address.missing', false],
            'text and empty text' => ['address.street && address.empty', false],
            'text and text' => ['address.street && address.city', true],
            'grouped text conditions' => ['(address.street || address.number) && (address.postal_code || address.city)', true],
            'text equal to a string' => ['address.street == "ΟΔΟΣ ΕΡΜΟΥ"', true],
            'text not equal to text' => ['address.street != address.city', true],
            'text with an operator character' => ['address.rate == "24%"', true],
            'text joined to text' => ['address.street + " " + address.number', 'ΟΔΟΣ ΕΡΜΟΥ 12'],
            'a number compared and text' => ['address.floor > 3 && address.city', true],
            'numbers' => ['address.floor * 2 + 1', 11],
            'a number as text added to a number' => ['address.number + 5', 17],
            'codes of digits joined' => ['address.series + address.number', '00112'],
            'quoted numbers joined' => ['"1" + "2"', '12'],
            'a number as text joined to a missing value' => ['address.number + address.missing', '12'],
            'a zero stored as text dividing' => ['address.ten / address.zero', 0],
            'a zero stored as text as modulus' => ['address.ten % address.zero', 0],
            'numbers stored as text divided' => ['address.ten / address.number', 10 / 12],
        ];
    }

    #[DataProvider('expressions')]
    public function test_it_evaluates_text_values_in_expressions(string $formula, mixed $expected)
    {
        $result = $this->evaluate($formula, ['address' => [
            'street' => 'ΟΔΟΣ ΕΡΜΟΥ',
            'number' => '12',
            'postal_code' => '10563',
            'city' => 'ΑΘΗΝΑ',
            'empty' => '',
            'rate' => '24%',
            'floor' => 5,
            'series' => '001',
            'ten' => '10',
            'zero' => '0',
        ]]);

        $this->assertSame($expected, $result);
    }

    public static function arithmeticOnText(): array
    {
        return [
            'text minus a number' => ['address.street - 1', "'-' needs numbers, got the text 'ΟΔΟΣ ΕΡΜΟΥ'"],
            'a percentage times a number' => ['address.rate * 2', "'*' needs numbers, got the text '24%'"],
            'a number divided by text' => ['10 / address.city', "'/' needs numbers, got the text 'ΑΘΗΝΑ'"],
        ];
    }

    #[DataProvider('arithmeticOnText')]
    public function test_arithmetic_on_text_is_an_error(string $formula, string $message)
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        $this->evaluate($formula, ['address' => ['street' => 'ΟΔΟΣ ΕΡΜΟΥ', 'rate' => '24%', 'city' => 'ΑΘΗΝΑ']]);
    }
}
