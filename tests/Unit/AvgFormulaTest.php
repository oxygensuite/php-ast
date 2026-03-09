<?php

namespace Tests\Unit;

use Tests\FormulaTestCase;

class AvgFormulaTest extends FormulaTestCase
{

    public function test_avg_with_simple_wildcard_path()
    {
        $result = $this->evaluate('AVG(PLUCK(lines; "quantity"))');

        // Average quantity: (2 + 3 + 1 + 5 + 4) / 5 = 3
        $this->assertEquals(3, $result);
    }

    public function test_avg_with_decimal_values()
    {
        $result = $this->evaluate('AVG(PLUCK(lines; "unit_price"))');

        // Average unit_price: (10 + 15 + 20 + 8 + 12) / 5 = 13
        $this->assertEquals(13, $result);
    }

    public function test_avg_with_expression()
    {
        $result = $this->evaluate('AVG(MAP(lines; quantity * unit_price))');

        // (2*10 + 3*15 + 1*20 + 5*8 + 4*12) / 5 = (20 + 45 + 20 + 40 + 48) / 5 = 34.6
        $this->assertEqualsWithDelta(34.6, $result, 0.01);
    }
}
