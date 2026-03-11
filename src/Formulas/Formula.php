<?php

namespace OxygenSuite\PhpAst\Formulas;

use OxygenSuite\PhpAst\AST\ASTEvaluator;

/**
 * Interface for formula functions that work with pre-evaluated arguments.
 */
interface Formula
{
    public const string SEPARATOR = ';';

    /**
     * @param array $arguments Pre-evaluated argument values
     * @param array $data Data context
     * @param ASTEvaluator $evaluator Evaluator for nested evaluations if needed
     */
    public function execute(array $arguments, array $data, ASTEvaluator $evaluator): float|int|string|array|null;
}
