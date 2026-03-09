<?php

namespace Tests\Unit;

use Tests\FormulaTestCase;

class FlatFormulaTest extends FormulaTestCase
{
    public function test_flat_with_depth_one()
    {
        $result = $this->evaluate('FLAT(flat_test_a; 1)', ['flat_test_a' => [[1, 2], [3, 4], [5, 6]]]);

        // Should flatten one level: [[1,2],[3,4],[5,6]] -> [1,2,3,4,5,6]
        $this->assertEquals([1, 2, 3, 4, 5, 6], $result);
    }

    public function test_flat_with_depth_two()
    {
        $result = $this->evaluate('FLAT(flat_test_b; 2)', ['flat_test_b' => [[[1, 2]], [[3, 4]], [[5, 6]]]]);

        // Should flatten two levels: [[[1,2]],[[3,4]],[[5,6]]] -> [1,2,3,4,5,6]
        $this->assertEquals([1, 2, 3, 4, 5, 6], $result);
    }

    public function test_flat_with_default_depth()
    {
        $result = $this->evaluate('FLAT(flat_test_c; 1)', ['flat_test_c' => [[1, 2], [3, [4, 5]]]]);

        // Default depth is 1: [[1,2],[3,[4,5]]] -> [1,2,3,[4,5]]
        $this->assertEquals([1, 2, 3, [4, 5]], $result);
    }

    public function test_flat_with_mixed_nested_levels()
    {
        $result = $this->evaluate('FLAT(flat_test_d; 1)', ['flat_test_d' => [[1, 2], 3, [4, [5, 6]]]]);

        // Flatten one level: [[1,2],3,[4,[5,6]]] -> [1,2,3,4,[5,6]]
        $this->assertEquals([1, 2, 3, 4, [5, 6]], $result);
    }

    public function test_flat_with_depth_zero()
    {
        $result = $this->evaluate('FLAT(flat_test_e; 0)', ['flat_test_e' => [[1, 2], [3, 4]]]);

        // Depth 0 means no flattening
        $this->assertEquals([[1, 2], [3, 4]], $result);
    }

    public function test_flat_with_deeply_nested_array()
    {
        $result = $this->evaluate('FLAT(flat_test_f; 3)', ['flat_test_f' => [[[[1, 2]]]]]);

        // Flatten 3 levels: [[[[1,2]]]] -> [1,2]
        $this->assertEquals([1, 2], $result);
    }

    public function test_flat_with_empty_arrays()
    {
        $result = $this->evaluate('FLAT(flat_test_g; 1)', ['flat_test_g' => [[], [1, 2], [], [3]]]);

        // Should flatten and remove empty arrays: [[],[1,2],[],[3]] -> [1,2,3]
        $this->assertEquals([1, 2, 3], $result);
    }

    public function test_flat_with_map_combination()
    {
        $result = $this->evaluate('SUM(FLAT(flat_test_h; 1))', ['flat_test_h' => [[2, 3], [1, 5], [4]]]);

        // Flatten [[2,3],[1,5],[4]] -> [2,3,1,5,4], sum = 15
        $this->assertEquals(15, $result);
    }
}
