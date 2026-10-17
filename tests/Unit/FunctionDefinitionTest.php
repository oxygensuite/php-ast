<?php

namespace Tests\Unit;

use OxygenSuite\PhpAst\Formulas\FormulaRegistry;
use OxygenSuite\PhpAst\Formulas\FunctionDefinition;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\FormulaTestCase;

class FunctionDefinitionTest extends FormulaTestCase
{
    /**
     * Every registered function, since definitions() throws for one without a definition
     *
     * @return array<string, array{string, FunctionDefinition}>
     */
    public static function definitions(): array
    {
        $cases = [];

        foreach (FormulaRegistry::definitions() as $name => $definition) {
            $cases[$name] = [$name, $definition];
        }

        return $cases;
    }

    #[DataProvider('definitions')]
    public function test_the_signature_names_the_function(string $name, FunctionDefinition $definition)
    {
        $this->assertStringStartsWith("$name(", $definition->signature);
    }

    #[DataProvider('definitions')]
    public function test_the_argument_bounds_are_consistent(string $name, FunctionDefinition $definition)
    {
        $this->assertGreaterThanOrEqual(1, $definition->minArguments);

        if ($definition->maxArguments !== null) {
            $this->assertGreaterThanOrEqual($definition->minArguments, $definition->maxArguments);
        }

        if ($definition->itemArgument !== null) {
            $this->assertLessThan($definition->maxArguments ?? PHP_INT_MAX, $definition->itemArgument);
        }

        if ($definition->listArgument !== null) {
            $this->assertLessThan($definition->maxArguments ?? PHP_INT_MAX, $definition->listArgument);
        } else {
            $this->assertFalse($definition->listArgumentAlone, 'Only a function with a list argument reads it alone.');
            $this->assertFalse($definition->keepsKeys, 'Only a function with a list argument keeps its keys.');
        }
    }

    #[DataProvider('definitions')]
    public function test_the_definition_of_a_handler_is_its_function_s(string $name, FunctionDefinition $definition)
    {
        $this->assertSame($definition, FormulaRegistry::definitionOf(FormulaRegistry::getHandlerByName($name)));
    }

    public function test_the_functions_that_keep_keys_return_their_list_keyed_as_given()
    {
        $data = ['items' => [3 => ['quantity' => 1], 7 => ['quantity' => 2]]];
        $formulas = ['FILTER' => 'FILTER(items; quantity > 0)', 'MAP' => 'MAP(items; quantity)'];

        $this->assertEqualsCanonicalizing(
            array_keys($formulas),
            array_keys(array_filter(FormulaRegistry::definitions(), fn(FunctionDefinition $definition) => $definition->keepsKeys)),
        );

        foreach ($formulas as $formula) {
            $this->assertSame([3, 7], array_keys($this->evaluate($formula, $data)));
        }
    }

    #[DataProvider('definitions')]
    public function test_the_example_evaluates_to_a_value(string $name, FunctionDefinition $definition)
    {
        $result = $this->evaluate($definition->example);

        // A formula the engine cannot parse or call comes back as its own text, so that is a failure too
        $this->assertNotSame($definition->example, $result, 'The example was returned unevaluated.');
        $this->assertNotEmpty($result, 'The example evaluated to an empty value, so it shows nothing useful.');
    }
}
