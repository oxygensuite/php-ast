<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;

readonly class FloorFunction implements Formula
{
    /**
     * Syntax: FLOOR(value)
     * Example: FLOOR(10.9) => 10
     */
    public function execute(array $arguments, array $data, ASTEvaluator $evaluator): float
    {
        if (empty($arguments)) {
            return 0;
        }

        $value = $arguments[0];

        if (is_array($value)) {
            $value = array_sum(array_map('floatval', $value));
        }

        return floor((float) $value);
    }
}
