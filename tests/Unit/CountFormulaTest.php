<?php

namespace Tests\Unit;

use Tests\FormulaTestCase;

class CountFormulaTest extends FormulaTestCase
{
    public function test_count_with_simple_wildcard_path()
    {
        $result = $this->evaluate('COUNT(PLUCK(lines; "quantity"))');

        // Count of lines: 5
        $this->assertEquals(5, $result);
    }

    public function test_count_with_filtered_data()
    {
        $result = $this->evaluate('COUNT(FILTER(lines; tax_category = 1))');

        // Lines with tax_category = 1: lines 1, 3, 5 = 3 items
        $this->assertEquals(3, $result);
    }

    public function test_count_with_expression()
    {
        $result = $this->evaluate('COUNT(MAP(lines; quantity * unit_price))');

        // All 5 lines have results
        $this->assertEquals(5, $result);
    }
}
