<?php

namespace Tests\Unit;

use Tests\FormulaTestCase;

class FilterFormulaTest extends FormulaTestCase
{
    public function test_filter_with_equals_operator()
    {
        $result = $this->evaluate('FILTER(lines; tax_category = 1)');
        $this->assertEquals([0, 2, 4], array_keys($result));
    }

    public function test_filter_with_greater_than_operator()
    {
        // Filter unit_price > 10 (lines 2, 3, 4: 15, 20, 12)
        $result = $this->evaluate('FILTER(lines; unit_price > 10)');
        $this->assertEquals([1, 2, 4], array_keys($result));
    }

    public function test_filter_with_less_than_operator()
    {
        // Filter quantity < 3 (lines 1, 3: 2, 1)
        $result = $this->evaluate('FILTER(lines; quantity < 3)');
        $this->assertEquals([0, 2], array_keys($result));
    }

    public function test_filter_with_greater_than_or_equal_operator()
    {
        // Filter quantity >= 3 (lines 1, 3: 2, 1)
        $result = $this->evaluate('FILTER(lines; quantity >= 3)');
        $this->assertEquals([1, 3, 4], array_keys($result));
    }

    public function test_filter_with_formula_as_comparison_value()
    {
        // Filter unit_price > SUM(5 + 5) = 10 (lines 2, 3, 5: 15, 20, 12)
        $result = $this->evaluate('FILTER(lines; unit_price > SUM(5 + 5))');
        $this->assertEquals([1, 2, 4], array_keys($result));
    }

    public function test_filter_with_nested_sum_comparison()
    {
        // Filter quantity > average (average = 15/5 = 3)
        // Lines where quantity > 3: line 4 (5), line 5 (4)
        $result = $this->evaluate('FILTER(lines; quantity > 3)');
        $this->assertEquals([3, 4], array_keys($result));
    }
}
