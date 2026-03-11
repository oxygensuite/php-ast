<?php

namespace OxygenSuite\PhpAst\Formulas;

use OxygenSuite\PhpAst\AST\ASTEvaluator;

/**
 * Abstract base class for formula functions
 * Template Method Pattern: Provides default implementation for backward compatibility
 * Liskov Substitution Principle: Subclasses can be used interchangeably
 */
abstract readonly class AbstractFormula implements Formula
{
    /**
     * Legacy method: parse string arguments and delegate to executeWithArgs
     *
     * @deprecated Override executeWithArgs instead
     */
    public function execute(string $arguments, array $data, FormulaEvaluator $evaluator): float|int|string|array
    {
        // Parse string arguments
        $args = FormulaParser::splitArguments($arguments);

        // Evaluate each argument using the old evaluator
        $evaluatedArgs = array_map(
            fn($arg) => $evaluator->evaluate($arg, $data),
            $args,
        );

        // Create a temporary AST evaluator to bridge the gap
        $astEvaluator = new ASTEvaluator();

        return $this->executeWithArgs($evaluatedArgs, $data, $astEvaluator);
    }

    /**
     * New method: work with pre-evaluated arguments
     * Subclasses should override this method
     */
    abstract public function executeWithArgs(array $arguments, array $data, ASTEvaluator $evaluator): float|int|string|array|null;
}
