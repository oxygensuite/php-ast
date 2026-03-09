<?php

namespace Tests\Unit;

use Tests\FormulaTestCase;

class IfFormulaTest extends FormulaTestCase
{
    public function test_if_with_simple_condition_true()
    {
        $result = $this->evaluate('IF(uid == "1"; "TRUE"; "FALSE")');
        $this->assertEquals('TRUE', $result);
    }

    public function test_if_with_simple_condition_false()
    {
        $result = $this->evaluate('IF(uid == "999"; "TRUE"; "FALSE")');
        $this->assertEquals('FALSE', $result);
    }

    public function test_if_with_numeric_comparison_greater_than()
    {
        // Total quantity = 15, check if > 10
        $result = $this->evaluate('IF(SUM(PLUCK(lines; "quantity")) > 10; "High"; "Low")');
        $this->assertEquals('High', $result);
    }

    public function test_if_with_numeric_comparison_less_than()
    {
        // Total quantity = 15, check if < 10
        $result = $this->evaluate('IF(SUM(PLUCK(lines; "quantity")) < 10; "Low"; "High")');
        $this->assertEquals('High', $result);
    }

    public function test_if_with_formula_in_true_branch()
    {
        // If uid == '1', return sum of quantities (15), else return 0
        $result = $this->evaluate('IF(uid == "1"; SUM(PLUCK(lines; "quantity")); 0)');
        $this->assertEquals(15, $result);
    }

    public function test_if_with_formula_in_false_branch()
    {
        // If uid == '999', return 0, else return the sum of quantities (15)
        $result = $this->evaluate('IF(uid == "999"; 0; SUM(PLUCK(lines; "quantity")))');
        $this->assertEquals(15, $result);
    }

    public function test_if_with_math_expression_in_branches()
    {
        // If total > 100, return (total / 10), else return (total / 20)
        // Total quantity = 15, so 15 > 10 is true, return 15/10 = 1.5
        $result = $this->evaluate('IF(SUM(PLUCK(lines; "quantity")) > 10; SUM(PLUCK(lines; "quantity")) / 10; SUM(PLUCK(lines; "quantity")) / 20)');
        $this->assertEquals(1.5, $result);
    }

    public function test_if_with_complex_nested_condition()
    {
        // If total_amount sum > 200, return "High Volume", else "Normal"
        // Sum = 214.52 > 200
        $result = $this->evaluate('IF(SUM(PLUCK(lines; "total_amount")) > 200; "High Volume"; "Normal")');
        $this->assertEquals('High Volume', $result);
    }
}
