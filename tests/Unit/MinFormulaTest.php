<?php

namespace Tests\Unit;

use Tests\FormulaTestCase;

class MinFormulaTest extends FormulaTestCase
{
    public function test_min_with_simple_wildcard_path()
    {
        $result = $this->evaluate('MIN(PLUCK(lines; "unit_price"))');

        // Minimum unit_price is 8 (line 4)
        $this->assertEquals(8, $result);
    }

    public function test_min_with_expression()
    {
        $result = $this->evaluate('MIN(MAP(lines; quantity * unit_price))');

        // Calculate quantity * unit_price for each line:
        // Line 1: 2*10=20
        // Line 2: 3*15=45
        // Line 3: 1*20=20
        // Line 4: 5*8=40
        // Line 5: 4*12=48
        // Minimum is 20 (lines 1 and 3)
        $this->assertEquals(20, $result);
    }

    public function test_min_with_negative_numbers()
    {
        $result = $this->evaluate('MIN(MAP(lines; 0 - discount_amount))');

        // Negative discounts: [0, -5, 0, 0, -2], minimum is -5
        $this->assertEquals(-5, $result);
    }
}
