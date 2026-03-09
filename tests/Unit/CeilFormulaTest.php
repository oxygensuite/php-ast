<?php

namespace Tests\Unit;

use Tests\FormulaTestCase;

class CeilFormulaTest extends FormulaTestCase
{
    public function test_ceil_with_decimal()
    {
        $result = $this->evaluate('CEIL(10.1)');

        $this->assertEquals(11, $result);
    }

    public function test_ceil_with_negative()
    {
        $result = $this->evaluate('CEIL(0 - 10.9)');
        $this->assertEquals(-10, $result);
    }

    public function test_ceil_with_formula()
    {
        $result = $this->evaluate('CEIL(AVG(PLUCK(lines; "quantity")))');

        // Average quantity: 15 / 5 = 3, ceil(3) = 3
        $this->assertEquals(3, $result);
    }
}
