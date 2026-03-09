<?php

namespace Tests\Unit;

use Tests\FormulaTestCase;

class RoundFormulaTest extends FormulaTestCase
{
    public function test_round_with_default_precision()
    {
        $result = $this->evaluate('ROUND(10.567)');
        $this->assertEquals(11, $result);
    }

    public function test_round_with_precision_two()
    {
        $result = $this->evaluate('ROUND(10.567; 2)');
        $this->assertEquals(10.57, $result);
    }

    public function test_round_with_precision_one()
    {
        $result = $this->evaluate('ROUND(10.567; 1)');
        $this->assertEquals(10.6, $result);
    }

    public function test_round_with_formula()
    {
        $result = $this->evaluate('ROUND(AVG(PLUCK(lines; "unit_price")); 2)');

        // Average unit_price: (10 + 15 + 20 + 8 + 12) / 5 = 13
        $this->assertEquals(13.00, $result);
    }
}
