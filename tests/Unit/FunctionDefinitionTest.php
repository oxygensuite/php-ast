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
