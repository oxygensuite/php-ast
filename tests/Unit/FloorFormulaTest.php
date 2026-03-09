<?php

namespace Tests\Unit;

use Tests\FormulaTestCase;

class FloorFormulaTest extends FormulaTestCase
{
    public function test_floor_with_decimal()
    {
        $result = $this->evaluate('FLOOR(10.9)');
        $this->assertEquals(10, $result);
    }

    public function test_floor_with_negative()
    {
        $result = $this->evaluate('FLOOR(0 - 10.1)');
        $this->assertEquals(-11, $result);
    }

    public function test_floor_with_formula()
    {
        $result = $this->evaluate('FLOOR(AVG(PLUCK(lines; "unit_price")))');

        // Average unit_price: 65 / 5 = 13, floor(13) = 13
        $this->assertEquals(13, $result);
    }
}
