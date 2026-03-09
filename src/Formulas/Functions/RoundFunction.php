<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\AbstractFormula;

readonly class RoundFunction extends AbstractFormula
{
    /**
     * Syntax: ROUND(value; precision)
     * Example: ROUND(10.567; 2) => 10.57
     * Example: ROUND(10.567) => 11 (default precision = 0)
     */
    public function executeWithArgs(array $arguments, array $data, ASTEvaluator $evaluator): float
    {
        if (empty($arguments)) {
            return 0;
        }

        $value = $arguments[0];
        $precision = isset($arguments[1]) ? (int) $arguments[1] : 0;

        if (is_array($value)) {
            $value = array_sum(array_map('floatval', $value));
        }

        return round((float) $value, $precision);
    }
}
