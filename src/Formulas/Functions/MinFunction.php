<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\AbstractFormula;

readonly class MinFunction extends AbstractFormula
{
    /**
     * Syntax: MIN(data/formula)
     */
    public function executeWithArgs(array $arguments, array $data, ASTEvaluator $evaluator): float
    {
        if (empty($arguments)) {
            return 0;
        }

        // If a single argument that's an array, find min
        if (count($arguments) === 1 && is_array($arguments[0])) {
            return empty($arguments[0]) ? 0 : min(array_map('floatval', $arguments[0]));
        }

        // If multiple arguments, find min of all
        $values = [];
        foreach ($arguments as $arg) {
            if (is_array($arg)) {
                array_push($values, ...array_map('floatval', $arg));
            } elseif (is_numeric($arg)) {
                $values[] = (float) $arg;
            }
        }

        return empty($values) ? 0 : min($values);
    }
}