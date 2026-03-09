<?php

namespace Tests\Unit;

use Tests\FormulaTestCase;

class WildcardTransformerTest extends FormulaTestCase
{
    private array $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data with nested structures
        $this->invoice = [
            'lines' => [
                [
                    'line_number' => 1,
                    'quantity' => 10,
                    'unit_price' => 100,
                    'net_amount' => 1000,
                    'vat_amount' => 100,
                    'taxes' => [
                        ['amount' => 10, 'type' => 'VAT'],
                        ['amount' => 5, 'type' => 'Sales'],
                    ],
                ],
                [
                    'line_number' => 2,
                    'quantity' => 20,
                    'unit_price' => 200,
                    'net_amount' => 4000,
                    'vat_amount' => 400,
                    'taxes' => [
                        ['amount' => 20, 'type' => 'VAT'],
                        ['amount' => 10, 'type' => 'Sales'],
                    ],
                ],
                [
                    'line_number' => 3,
                    'quantity' => 30,
                    'unit_price' => 300,
                    'net_amount' => 9000,
                    'vat_amount' => 300,
                    'taxes' => [
                        ['amount' => 30, 'type' => 'VAT'],
                        ['amount' => 15, 'type' => 'Sales'],
                    ],
                ],
            ],
        ];
    }

    public function test_single_wildcard_with_sum()
    {
        // SUM(lines.*.quantity) should equal 10 + 20 + 30 = 60
        $result = $this->evaluate('SUM(lines.*.quantity)', $this->invoice);
        $this->assertEquals(60, $result);
    }

    public function test_single_wildcard_pluck_equivalent()
    {
        // lines.*.quantity should be equivalent to PLUCK(lines; "quantity")
        $wildcardResult = $this->evaluate('lines.*.quantity', $this->invoice);
        $pluckResult = $this->evaluate('PLUCK(lines; "quantity")', $this->invoice);

        $this->assertEquals($pluckResult, $wildcardResult);
    }

    public function test_nested_wildcard_with_sum()
    {
        // SUM(lines.*.taxes.*.amount) should sum all tax amounts
        // Line 1: 10 + 5 = 15
        // Line 2: 20 + 10 = 30
        // Line 3: 30 + 15 = 45
        // Total: 90
        $result = $this->evaluate('SUM(lines.*.taxes.*.amount)', $this->invoice);
        $this->assertEquals(90, $result);
    }

    public function test_nested_wildcard_pluck_equivalent()
    {
        // lines.*.taxes.*.amount should be equivalent to PLUCK(FLAT(PLUCK(lines; "taxes")); "amount")
        $wildcardResult = $this->evaluate('lines.*.taxes.*.amount', $this->invoice);
        $pluckResult = $this->evaluate('PLUCK(FLAT(PLUCK(lines; "taxes")); "amount")', $this->invoice);

        $this->assertEquals($pluckResult, $wildcardResult);
    }

    public function test_wildcard_with_avg()
    {
        // AVG(lines.*.quantity) should equal (10 + 20 + 30) / 3 = 20
        $result = $this->evaluate('AVG(lines.*.quantity)', $this->invoice);
        $this->assertEquals(20, $result);
    }

    public function test_wildcard_with_min()
    {
        // MIN(lines.*.quantity) should equal 10
        $result = $this->evaluate('MIN(lines.*.quantity)', $this->invoice);
        $this->assertEquals(10, $result);
    }

    public function test_wildcard_with_max()
    {
        // MAX(lines.*.quantity) should equal 30
        $result = $this->evaluate('MAX(lines.*.quantity)', $this->invoice);
        $this->assertEquals(30, $result);
    }

    public function test_wildcard_with_count()
    {
        // COUNT(lines.*.quantity) should equal 3
        $result = $this->evaluate('COUNT(lines.*.quantity)', $this->invoice);
        $this->assertEquals(3, $result);
    }

    public function test_wildcard_in_arithmetic_expression()
    {
        // Arithmetic expressions with wildcards should work
        $result = $this->evaluate('SUM(lines.*.net_amount) + SUM(lines.*.vat_amount)', $this->invoice);
        // Total net amount: 1000 + 4000 + 9000 = 14000
        // Total VAT amount: 100 + 400 + 300 = 800
        // Total: 14000 + 800 = 14800
        $this->assertEquals(14800, $result);
    }

    public function test_nested_wildcard_with_min()
    {
        // MIN(lines.*.taxes.*.amount) should find the minimum tax amount
        $result = $this->evaluate('MIN(lines.*.taxes.*.amount)', $this->invoice);
        $this->assertEquals(5, $result);
    }

    public function test_nested_wildcard_with_max()
    {
        // MAX(lines.*.taxes.*.amount) should find the maximum tax amount
        $result = $this->evaluate('MAX(lines.*.taxes.*.amount)', $this->invoice);
        $this->assertEquals(30, $result);
    }

    public function test_nested_wildcard_with_count()
    {
        // COUNT(lines.*.taxes.*.amount) should count all tax entries
        // 3 lines × 2 taxes each = 6
        $result = $this->evaluate('COUNT(lines.*.taxes.*.amount)', $this->invoice);
        $this->assertEquals(6, $result);
    }

    public function test_triple_nested_wildcard()
    {
        // Create more complex nested data
        $complexData = [
            'items' => [
                [
                    'details' => [
                        ['props' => [['value' => 1], ['value' => 2]]],
                        ['props' => [['value' => 3], ['value' => 4]]],
                    ],
                ],
                [
                    'details' => [
                        ['props' => [['value' => 5], ['value' => 6]]],
                    ],
                ],
            ],
        ];

        // SUM(items.*.details.*.props.*.value) should sum all values
        // 1 + 2 + 3 + 4 + 5 + 6 = 21
        $result = $this->evaluate('SUM(items.*.details.*.props.*.value)', $complexData);
        $this->assertEquals(21, $result);
    }

    public function test_wildcard_returns_array_when_not_in_function()
    {
        // lines.*.quantity should return an array when not wrapped in a function
        $result = $this->evaluate('lines.*.quantity', $this->invoice);
        $this->assertIsArray($result);
        $this->assertEquals([10, 20, 30], $result);
    }

    public function test_nested_wildcard_returns_flat_array()
    {
        // lines.*.taxes.*.amount should return a flat array of all amounts
        $result = $this->evaluate('lines.*.taxes.*.amount', $this->invoice);
        $this->assertIsArray($result);
        $this->assertEquals([10, 5, 20, 10, 30, 15], $result);
    }
}
