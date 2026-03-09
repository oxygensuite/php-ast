<?php

namespace OxygenSuite\PhpAst\Formulas;

use OxygenSuite\PhpAst\AST\ASTEvaluator;

/**
 * Interface for formula function handlers
 * Interface Segregation Principle: Minimal interface for all formula functions
 */
interface Formula
{
    public const string SEPARATOR = ';';

    /**
     * Execute with string arguments (legacy support)
     * @deprecated Use executeWithArgs instead
     */
    public function execute(string $arguments, array $data, FormulaEvaluator $evaluator): float|int|string|array;

    /**
     * Execute with pre-evaluated arguments (new AST-based approach)
     * @param array $arguments Pre-evaluated argument values
     * @param array $data Data context
     * @param ASTEvaluator $evaluator Evaluator for nested evaluations if needed
     */
    public function executeWithArgs(array $arguments, array $data, ASTEvaluator $evaluator): float|int|string|array|null;
}
