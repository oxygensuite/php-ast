<?php

namespace Tests\Unit;

use OxygenSuite\PhpAst\AST\FunctionNode;
use OxygenSuite\PhpAst\AST\ParseException;
use OxygenSuite\PhpAst\AST\Tokenizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\FormulaTestCase;

class FormulaSyntaxTest extends FormulaTestCase
{
    public static function malformedFormulas(): array
    {
        return [
            'unknown function before a known one' => ['XYZ(MAX(7; 9))', "Unknown function 'XYZ' at position 0"],
            'lower-case function name' => ['SUM(round(1))', "Unknown function 'round' at position 4"],
            'space before the parenthesis' => ['SUM(ROUND (1))', "Expected ';' or ')' but found character '(' at position 10"],
            'text after the expression' => ['SUM(1; 2); 999', "Unexpected character ';' at position 9"],
            'empty argument' => ['IF(1;; 0)', 'Missing argument of IF() at position 5'],
            'empty first argument' => ['ROUND(; 2)', 'Missing argument of ROUND() at position 6'],
            'trailing separator' => ['ROUND(1;)', 'Missing argument of ROUND() at position 8'],
            'trailing separator and space' => ['ROUND(1; )', 'Missing argument of ROUND() at position 9'],
        ];
    }

    #[DataProvider('malformedFormulas')]
    public function test_malformed_formula_is_rejected(string $formula, string $message)
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage($message);

        (new Tokenizer())->tokenize($formula);
    }

    public static function malformedExpressions(): array
    {
        return [
            'an operator without its right side' => ['uid +', 'Unexpected end of the formula at position 5'],
            'text after a comparison' => ['uid == 1 2', "Unexpected character '2' at position 9"],
            'an unclosed call' => ['IF(uid', "Expected ';' or ')' but found end of the formula at position 6"],
            'an unclosed parenthesis' => ['(uid + 1', "Expected ')' but found end of the formula at position 8"],
        ];
    }

    #[DataProvider('malformedExpressions')]
    public function test_malformed_expression_is_rejected_instead_of_read_as_zero(string $formula, string $message)
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage($message);

        $this->evaluate($formula);
    }

    public function test_formula_longer_than_the_limit_is_rejected()
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Formula exceeds maximum allowed length');

        $this->evaluate(str_repeat('1 + ', 2500) . '1');
    }

    public function test_function_is_read_only_at_its_own_position()
    {
        $node = (new Tokenizer())->tokenize('IF(COUNT(lines) > 0; MAX(1; 2); 0)');

        $this->assertInstanceOf(FunctionNode::class, $node);
        $this->assertSame('IF', $node->name);
        $this->assertCount(3, $node->arguments);
    }

    public static function wrongArgumentCounts(): array
    {
        return [
            'too many' => ['ROUND(10.567; 2; 3)', 'ROUND() expects 1 to 2 argument(s), 3 given'],
            'too few' => ['IF(1; 2)', 'IF() expects 3 argument(s), 2 given'],
            'none' => ['ROUND( )', 'ROUND() expects 1 to 2 argument(s), 0 given'],
            'none for a function taking any number' => ['SUM()', 'SUM() expects at least 1 argument(s), 0 given'],
            'nested' => ['SUM(MAP(lines))', 'MAP() expects 2 argument(s), 1 given'],
        ];
    }

    #[DataProvider('wrongArgumentCounts')]
    public function test_call_with_wrong_argument_count_is_rejected(string $formula, string $message)
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage($message);

        $this->evaluate($formula);
    }

    public function test_wrong_argument_count_is_rejected_where_the_data_never_reaches_the_call()
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('ROUND() expects 1 to 2 argument(s), 4 given');

        $this->evaluate('MAP(lines; ROUND(quantity; 1; 2; 3))', ['lines' => []]);
    }

    public static function multiplications(): array
    {
        return [
            'variables' => ['SUM(total*qty)', 11.1],
            'variable and call' => ['SUM(qty*ROUND(total; 1))', 11.2],
            'wildcard path' => ['SUM(lines.*.quantity)*2', 30],
        ];
    }

    #[DataProvider('multiplications')]
    public function test_star_without_spaces_multiplies(string $formula, int|float $expected)
    {
        $this->assertEqualsWithDelta($expected, $this->evaluate($formula, ['total' => 5.55, 'qty' => 2]), 0.0001);
    }

    public static function negations(): array
    {
        return [
            'number' => ['ROUND(-2.5)', -3],
            'variable' => ['ROUND(-total; 1)', -5.6],
            'after an operator' => ['SUM(total * -qty)', -11.1],
            'call' => ['ROUND(-ROUND(total; 1))', -6],
            'bracket' => ['SUM(-(total + qty))', -7.55],
            'twice' => ['SUM(- -qty)', 2],
        ];
    }

    #[DataProvider('negations')]
    public function test_leading_minus_negates_the_term_after_it(string $formula, int|float $expected)
    {
        $this->assertEqualsWithDelta($expected, $this->evaluate($formula, ['total' => 5.55, 'qty' => 2]), 0.0001);
    }
}
