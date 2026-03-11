<?php

namespace Tests\Unit;

use Tests\FormulaTestCase;

class VariableInterpolationTest extends FormulaTestCase
{
    public function test_simple_variable_interpolation()
    {
        $result = $this->evaluate('uid');
        $this->assertEquals('1', $result);
    }

    public function test_nested_property_access()
    {
        $result = $this->evaluate('lines.0.quantity');
        $this->assertEquals(2.0, $result);
    }

    public function test_nested_property_unit_price()
    {
        $result = $this->evaluate('lines.0.unit_price');
        $this->assertEquals(10.0, $result);
    }

    public function test_deep_nested_property_access()
    {
        $result = $this->evaluate('lines.1.tax_amount');
        $this->assertEquals(10.8, $result);
    }

    public function test_variable_interpolation_in_arithmetic()
    {
        $result = $this->evaluate('uid + 5');
        $this->assertEquals(6, $result);
    }

    public function test_multiple_variables_in_expression()
    {
        $result = $this->evaluate('lines.0.quantity + lines.1.quantity');
        $this->assertEquals(5.0, $result);
    }

    public function test_variable_in_multiplication()
    {
        $result = $this->evaluate('lines.0.quantity * lines.0.unit_price');
        $this->assertEquals(20.0, $result);
    }

    public function test_variable_in_division()
    {
        $result = $this->evaluate('lines.0.net_amount / lines.0.quantity');
        $this->assertEquals(10.0, $result);
    }

    public function test_variable_in_subtraction()
    {
        $result = $this->evaluate('lines.0.total_amount - lines.0.tax_amount');
        $this->assertEquals(20.0, $result);
    }

    public function test_complex_arithmetic_with_variables()
    {
        $result = $this->evaluate('lines.0.quantity * lines.0.unit_price + lines.1.quantity * lines.1.unit_price');
        $this->assertEquals(65.0, $result);
    }

    public function test_variable_with_parentheses()
    {
        $result = $this->evaluate('(lines.0.quantity + lines.1.quantity) * 2');
        $this->assertEquals(10.0, $result);
    }

    public function test_variable_interpolation_with_context_override()
    {
        $result = $this->evaluate('foo', ['foo' => 'bar']);
        $this->assertEquals('bar', $result);
    }

    public function test_variable_interpolation_numeric_context()
    {
        $result = $this->evaluate('foo', ['foo' => 42]);
        $this->assertEquals(42, $result);
    }

    public function test_variable_interpolation_float_context()
    {
        $result = $this->evaluate('foo', ['foo' => 3.14]);
        $this->assertEquals(3.14, $result);
    }

    public function test_variable_interpolation_array_context()
    {
        $result = $this->evaluate('foo', ['foo' => [1, 2, 3]]);
        $this->assertEquals([1, 2, 3], $result);
    }

    public function test_variable_interpolation_null_value()
    {
        $result = $this->evaluate('foo', ['foo' => null]);
        $this->assertNull($result);
    }

    public function test_variable_interpolation_boolean_true()
    {
        $result = $this->evaluate('foo', ['foo' => true]);
        $this->assertTrue($result);
    }

    public function test_variable_interpolation_boolean_false()
    {
        $result = $this->evaluate('foo', ['foo' => false]);
        $this->assertFalse($result);
    }

    public function test_variable_interpolation_zero()
    {
        $result = $this->evaluate('foo', ['foo' => 0]);
        $this->assertEquals(0, $result);
    }

    public function test_variable_interpolation_empty_string()
    {
        $result = $this->evaluate('foo', ['foo' => '']);
        $this->assertEquals('', $result);
    }

    public function test_variable_with_underscore()
    {
        $result = $this->evaluate('foo_bar', ['foo_bar' => 'baz']);
        $this->assertEquals('baz', $result);
    }

    public function test_variable_with_numbers()
    {
        $result = $this->evaluate('foo123', ['foo123' => 'test']);
        $this->assertEquals('test', $result);
    }

    public function test_variable_interpolation_in_comparison()
    {
        $result = $this->evaluate('lines.0.quantity > 1');
        $this->assertTrue($result);
    }

    public function test_variable_interpolation_equality_check()
    {
        $result = $this->evaluate('lines.0.quantity == 2');
        $this->assertTrue($result);
    }

    public function test_variable_interpolation_not_equals()
    {
        $result = $this->evaluate('lines.0.quantity != 3');
        $this->assertTrue($result);
    }

    public function test_variable_interpolation_less_than()
    {
        $result = $this->evaluate('lines.0.quantity < 5');
        $this->assertTrue($result);
    }

    public function test_variable_interpolation_greater_or_equal()
    {
        $result = $this->evaluate('lines.0.quantity >= 2');
        $this->assertTrue($result);
    }

    public function test_variable_interpolation_less_or_equal()
    {
        $result = $this->evaluate('lines.0.quantity <= 2');
        $this->assertTrue($result);
    }

    public function test_variable_interpolation_with_and_operator()
    {
        $result = $this->evaluate('lines.0.quantity > 1 && lines.0.unit_price > 5');
        $this->assertTrue($result);
    }

    public function test_variable_interpolation_with_or_operator()
    {
        $result = $this->evaluate('lines.0.quantity > 10 || lines.0.unit_price > 5');
        $this->assertTrue($result);
    }

    public function test_variable_interpolation_modulo()
    {
        $result = $this->evaluate('mark % 2');
        $this->assertEquals(1, $result);
    }

    public function test_variable_interpolation_power()
    {
        $result = $this->evaluate('lines.0.quantity ** 2');
        $this->assertEquals(4.0, $result);
    }

    public function test_variable_nonexistent_returns_null()
    {
        $result = $this->evaluate('nonexistent_var');
        $this->assertNull($result);
    }

    public function test_variable_nonexistent_nested_returns_null()
    {
        $result = $this->evaluate('lines.0.nonexistent');
        $this->assertNull($result);
    }

    public function test_string_variable_concatenation()
    {
        $result = $this->evaluate('foo', ['foo' => 'hello', 'bar' => 'world']);
        $this->assertEquals('hello', $result);
    }

    public function test_variable_with_spaceship_operator()
    {
        $result = $this->evaluate('lines.0.quantity <=> lines.1.quantity');
        $this->assertEquals(-1, $result);
    }

    public function test_variable_with_null_coalescing()
    {
        $result = $this->evaluate('nonexistent ?? 42', ['nonexistent' => null]);
        $this->assertEquals(42, $result);
    }

    public function test_variable_with_elvis_operator()
    {
        $result = $this->evaluate('foo ?: 42', ['foo' => 0]);
        // Elvis operator returns the right side if the left is falsy
        $this->assertEquals(42, $result);
    }

    public function test_variable_in_nested_expression()
    {
        $result = $this->evaluate('(lines.0.quantity + lines.1.quantity) * (lines.0.unit_price + lines.1.unit_price)');
        $this->assertEquals(125.0, $result);
    }

    public function test_variable_mixed_with_literals()
    {
        $result = $this->evaluate('lines.0.quantity * 10 + 5');
        $this->assertEquals(25.0, $result);
    }

    public function test_variable_in_complex_boolean_expression()
    {
        $result = $this->evaluate('(lines.0.quantity > 1 && lines.0.unit_price < 15) || lines.0.tax_amount > 10');
        $this->assertTrue($result);
    }

    public function test_case_sensitive_variable_names()
    {
        $result = $this->evaluate('Uid');
        $this->assertNull($result);
    }

    public function test_variable_with_multiple_nested_levels()
    {
        $data = [
            'level1' => [
                'level2' => [
                    'level3' => 'deep value',
                ],
            ],
        ];
        $result = $this->evaluate('level1.level2.level3', $data);
        $this->assertEquals('deep value', $result);
    }

    public function test_variable_array_numeric_index()
    {
        $result = $this->evaluate('lines.4.line_number');
        $this->assertEquals(5, $result);
    }

    public function test_variable_with_negative_number()
    {
        $result = $this->evaluate('foo', ['foo' => -10]);
        $this->assertEquals(-10, $result);
    }

    public function test_variable_interpolation_preserves_type_string()
    {
        $result = $this->evaluate('foo', ['foo' => '123']);
        $this->assertIsString($result);
        $this->assertEquals('123', $result);
    }

    public function test_variable_interpolation_preserves_type_int()
    {
        $result = $this->evaluate('foo', ['foo' => 123]);
        $this->assertSame(123, $result);
    }

    public function test_variable_interpolation_preserves_type_float()
    {
        $result = $this->evaluate('foo', ['foo' => 123.45]);
        $this->assertSame(123.45, $result);
    }

    public function test_variable_percentage_value_not_treated_as_modulo()
    {
        $result = $this->evaluate('foo', ['foo' => '19%']);
        $this->assertEquals('19%', $result);
    }
}
