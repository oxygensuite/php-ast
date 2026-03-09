<?php

namespace Tests\Unit;

use Tests\FormulaTestCase;

class MapFormulaTest extends FormulaTestCase
{
    public function test_map_with_nested_sum_in_callback()
    {
        $result = $this->evaluate('MAP(lines; quantity * unit_price - discount_amount)');

        // Expected: [20, 40, 20, 40, 46]
        $this->assertEquals([20, 40, 20, 40, 46], $result);
    }

    public function test_map_with_filter_and_sum_in_callback()
    {
        $result = $this->evaluate('MAP(lines; IF(unit_price > 10; quantity * unit_price - discount_amount; 0))');

        // For lines with unit_price > 10 (lines 2,3,5):
        // Line 2: (3*15-5) = 40
        // Line 3: (1*20-0) = 20
        // Line 5: (4*12-2) = 46
        // For lines with unit_price <= 10 (lines 1,4): return 0
        // Expected: [0, 40, 20, 0, 46]
        $this->assertEquals([0, 40, 20, 0, 46], $result);
    }
}
