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

    public function acceptsArgumentCount(int $count): bool
    {
        return $count >= $this->minArguments && ($this->maxArguments === null || $count <= $this->maxArguments);
    }

    /**
     * How many arguments the function takes, for messages: "2", "at least 1" or "1 to 2"
     */
    public function expectedArguments(): string
    {
        return match (true) {
            $this->maxArguments === $this->minArguments => (string) $this->minArguments,
            $this->maxArguments === null => "at least {$this->minArguments}",
            default => "{$this->minArguments} to {$this->maxArguments}",
        };
    }
}
