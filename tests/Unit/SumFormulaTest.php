<?php

namespace Tests\Unit;

use Tests\FormulaTestCase;

class SumFormulaTest extends FormulaTestCase
{
    public function test_sum_with_simple_wildcard_path()
    {
        // Sum quantities: 2 + 3 + 1 + 5 + 4 = 15
        $result = $this->evaluate('SUM(PLUCK(lines; "quantity"))');
        $this->assertEquals('15', $result);
    }

    public function test_sum_with_wildcard_path_decimal_values()
    {
        // Sum total_amount: 24.8 + 55.8 + 24.8 + 49.6 + 59.52 = 214.52
        $result = $this->evaluate('SUM(PLUCK(lines; "total_amount"))');
        $this->assertEqualsWithDelta(214.52, (float) $result, 0.01);
    }

    public function test_sum_with_simple_addition()
    {
        $result = $this->evaluate('SUM(10 + 20 + 30)');
        $this->assertEquals('60', $result);
    }

    public function test_sum_with_mixed_operations()
    {
        $result = $this->evaluate('SUM(10 + 20 * 2 - 5)');
        $this->assertEquals('45', $result);
    }

    public function test_sum_with_parentheses()
    {
        $result = $this->evaluate('SUM((10 + 20) * 2)');
        $this->assertEquals('60', $result);
    }

    public function test_sum_with_row_wise_complex_expression()
    {
        $result = $this->evaluate('SUM(MAP(lines; quantity * unit_price - discount_amount))');

        // (quantity * unit_price) - discount for each line:
        // (2*10-0) + (3*15-5) + (1*20-0) + (5*8-0) + (4*12-2) = 20 + 40 + 20 + 40 + 46 = 166
        $this->assertEquals(166, $result);
    }

    public function test_sum_with_nested_sum()
    {
        $result = $this->evaluate('SUM(10 + 20 + SUM(5 + 5))');
        $this->assertEquals(40, $result);
    }

    public function test_sum_with_negative_numbers()
    {
        $result = $this->evaluate('SUM(0 - 10 - 20 + 5)');
        $this->assertEquals(-25, $result);
    }
}
