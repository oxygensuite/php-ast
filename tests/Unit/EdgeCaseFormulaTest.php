<?php

namespace Tests\Unit;

use Tests\FormulaTestCase;

class EdgeCaseFormulaTest extends FormulaTestCase
{
    public function test_sum_with_empty_array()
    {
        $result = $this->evaluate('SUM(FILTER(lines; quantity > 100))');

        // No lines match, should return 0
        $this->assertEquals(0, $result);
    }

    public function test_avg_with_empty_array()
    {
        $result = $this->evaluate('AVG(FILTER(lines; quantity > 100))');

        // No lines match, should return 0
        $this->assertEquals(0, $result);
    }

    public function test_min_with_empty_array()
    {
        $result = $this->evaluate('MIN(FILTER(lines; quantity > 100))');

        // No lines match, should return 0
        $this->assertEquals(0, $result);
    }

    public function test_max_with_empty_array()
    {
        $result = $this->evaluate('MAX(FILTER(lines; quantity > 100))');

        // No lines match, should return 0
        $this->assertEquals(0, $result);
    }

    public function test_count_with_empty_array()
    {
        $result = $this->evaluate('COUNT(FILTER(lines; quantity > 100))');

        // No lines match, should return 0
        $this->assertEquals(0, $result);
    }

    public function test_division_by_zero_returns_zero()
    {
        $result = $this->evaluate('SUM(10 / 0)');

        // Division by zero should be handled gracefully
        $this->assertEquals(0, $result);
    }
}
