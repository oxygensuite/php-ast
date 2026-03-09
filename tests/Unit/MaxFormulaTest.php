<?php

namespace Tests\Unit;

use Tests\FormulaTestCase;

class MaxFormulaTest extends FormulaTestCase
{
    public function test_max_with_simple_wildcard_path()
    {
        $result = $this->evaluate('MAX(PLUCK(lines; "unit_price"))');

        // Maximum unit_price is 20 (line 3)
        $this->assertEquals(20, $result);
    }

    public function test_max_with_expression()
    {
        $result = $this->evaluate('MAX(MAP(lines; quantity * unit_price))');

        // Calculate quantity * unit_price for each line:
        // Line 1: 2*10=20
        // Line 2: 3*15=45
        // Line 3: 1*20=20
        // Line 4: 5*8=40
        // Line 5: 4*12=48
        // Maximum is 48 (line 5)
        $this->assertEquals(48, $result);
    }

    public function test_max_with_negative_numbers()
    {
        $result = $this->evaluate('MAX(MAP(lines; 0 - discount_amount))');

        // Negative discounts: [0, -5, 0, 0, -2], maximum is 0
        $this->assertEquals(0, $result);
    }
}
