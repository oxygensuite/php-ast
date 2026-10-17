<?php

namespace OxygenSuite\PhpAst\Formulas;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\AST\ParseException;
use OxygenSuite\PhpAst\ContextProvider\ContextProvider;

/**
 * Evaluates a formula against data: parses it once into an AST, cached, and evaluates that, so each variable is read
 * with its value as it is, a text value such as "ΟΔΟΣ ΕΡΜΟΥ" or "24%" compared and combined as text.
 */
class FormulaEvaluator
{
    private ASTEvaluator $astEvaluator;

    public function __construct(?ContextProvider $contextProvider = null)
    {
        $this->astEvaluator = new ASTEvaluator($contextProvider);
    }

    /**
     * @throws ParseException When the formula cannot be read
     */
    public function evaluate(string $formula, array $data): mixed
    {
        return $this->astEvaluator->evaluate($formula, $data);
    }

    /**
     * Get AST cache statistics
     */
    public function getCacheStats(): array
    {
        return $this->astEvaluator->getCacheStats();
    }

    /**
     * Clear AST cache
     */
    public function clearCache(): void
    {
        $this->astEvaluator->clearCache();
    }
}
