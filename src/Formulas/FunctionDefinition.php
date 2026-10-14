<?php

namespace OxygenSuite\PhpAst\Formulas;

use Attribute;

/**
 * Describes a formula function for its users: how it is called, what it does and how many arguments it takes.
 * Every function class in ./Functions carries one, and FormulaRegistry::definitions() lists them.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class FunctionDefinition
{
    /**
     * @param string $signature How the function is called, e.g. "ROUND(value; precision?)"
     * @param string $description What the function returns
     * @param string $example A formula using the function
     * @param int $minArguments The fewest arguments the function needs
     * @param int|null $maxArguments The most arguments the function takes, or null when it takes any number
     * @param int|null $itemArgument The (zero-based) argument evaluated once per item of the first argument, e.g. FILTER's condition
     */
    public function __construct(
        public string $signature,
        public string $description,
        public string $example,
        public int $minArguments,
        public ?int $maxArguments,
        public ?int $itemArgument = null,
    ) {}
}
