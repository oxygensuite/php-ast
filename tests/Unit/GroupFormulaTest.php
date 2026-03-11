<?php

namespace Tests\Unit;

use Tests\FormulaTestCase;

class GroupFormulaTest extends FormulaTestCase
{
    public function test_group_by_tax_category()
    {
        $result = $this->evaluate('GROUP(lines; "tax_category")');

        // Should group by tax_category: 1, 2, 3
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(2, $result);
        $this->assertArrayHasKey(3, $result);

        // Category 1 should have 3 lines (line 1, 3, 5)
        $this->assertCount(3, $result[1]);
        $this->assertEquals(1, $result[1][0]['line_number']);
        $this->assertEquals(3, $result[1][1]['line_number']);
        $this->assertEquals(5, $result[1][2]['line_number']);

        // Category 2 should have 1 line (line 2)
        $this->assertCount(1, $result[2]);
        $this->assertEquals(2, $result[2][0]['line_number']);

        // Category 3 should have 1 line (line 4)
        $this->assertCount(1, $result[3]);
        $this->assertEquals(4, $result[3][0]['line_number']);
    }

    public function test_group_simple_array()
    {
        $result = $this->evaluate('GROUP(items; "category")', [
            'items' => [
                ['id' => 1, 'category' => 'A', 'value' => 10],
                ['id' => 2, 'category' => 'B', 'value' => 20],
                ['id' => 3, 'category' => 'A', 'value' => 30],
                ['id' => 4, 'category' => 'C', 'value' => 40],
                ['id' => 5, 'category' => 'B', 'value' => 50],
            ],
        ]);

        // Should group by category: A, B, C
        $this->assertArrayHasKey('A', $result);
        $this->assertArrayHasKey('B', $result);
        $this->assertArrayHasKey('C', $result);

        // Category A should have 2 items
        $this->assertCount(2, $result['A']);
        $this->assertEquals(1, $result['A'][0]['id']);
        $this->assertEquals(3, $result['A'][1]['id']);

        // Category B should have 2 items
        $this->assertCount(2, $result['B']);
        $this->assertEquals(2, $result['B'][0]['id']);
        $this->assertEquals(5, $result['B'][1]['id']);

        // Category C should have 1 item
        $this->assertCount(1, $result['C']);
        $this->assertEquals(4, $result['C'][0]['id']);
    }

    public function test_group_with_numeric_keys()
    {
        $result = $this->evaluate('GROUP(products; "type")', [
            'products' => [
                ['name' => 'Product A', 'type' => 1, 'price' => 100],
                ['name' => 'Product B', 'type' => 2, 'price' => 200],
                ['name' => 'Product C', 'type' => 1, 'price' => 150],
                ['name' => 'Product D', 'type' => 3, 'price' => 300],
            ],
        ]);

        // Should group by type: 1, 2, 3
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(2, $result);
        $this->assertArrayHasKey(3, $result);

        // Type 1 should have 2 products
        $this->assertCount(2, $result[1]);
        $this->assertEquals('Product A', $result[1][0]['name']);
        $this->assertEquals('Product C', $result[1][1]['name']);

        // Type 2 should have 1 product
        $this->assertCount(1, $result[2]);
        $this->assertEquals('Product B', $result[2][0]['name']);

        // Type 3 should have 1 product
        $this->assertCount(1, $result[3]);
        $this->assertEquals('Product D', $result[3][0]['name']);
    }

    public function test_group_empty_array()
    {
        $result = $this->evaluate('GROUP(empty; "key")', ['empty' => []]);

        // Should return empty array
        $this->assertEquals([], $result);
    }

    public function test_group_with_missing_key()
    {
        $result = $this->evaluate('GROUP(items; "category")', [
            'items' => [
                ['id' => 1, 'category' => 'A'],
                ['id' => 2], // Missing category key
                ['id' => 3, 'category' => 'A'],
            ],
        ]);

        // Should only group items that have the key
        $this->assertArrayHasKey('A', $result);
        $this->assertCount(2, $result['A']);
        $this->assertEquals(1, $result['A'][0]['id']);
        $this->assertEquals(3, $result['A'][1]['id']);

        // Item without category should not be in result
        $this->assertArrayNotHasKey(2, $result);
    }

    public function test_group_combined_with_sum()
    {
        // First group by tax_category, then sum net_amount for category 1
        $grouped = $this->evaluate('GROUP(lines; "tax_category")');

        // Calculate sum manually for verification
        $category1Sum = 0;
        foreach ($grouped[1] as $line) {
            $category1Sum += $line['net_amount'];
        }

        // Category 1 has lines 1, 3, 5 with net amounts 20, 20, 48 = 88
        $this->assertEquals(88, $category1Sum);
    }

    public function test_group_with_boolean_values()
    {
        $result = $this->evaluate('GROUP(tasks; "completed")', [
            'tasks' => [
                ['name' => 'Task 1', 'completed' => true],
                ['name' => 'Task 2', 'completed' => false],
                ['name' => 'Task 3', 'completed' => true],
                ['name' => 'Task 4', 'completed' => false],
            ],
        ]);

        // Should group by completed status
        $this->assertArrayHasKey(true, $result);
        $this->assertArrayHasKey(false, $result);

        // Completed tasks
        $this->assertCount(2, $result[true]);
        $this->assertEquals('Task 1', $result[true][0]['name']);
        $this->assertEquals('Task 3', $result[true][1]['name']);

        // Incomplete tasks
        $this->assertCount(2, $result[false]);
        $this->assertEquals('Task 2', $result[false][0]['name']);
        $this->assertEquals('Task 4', $result[false][1]['name']);
    }
}
