<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\AbstractFormula;

readonly class CeilFunction extends AbstractFormula
{
    /**
     * Syntax: CEIL(value)
     * Example: CEIL(10.1) => 11
     */
    public function executeWithArgs(array $arguments, array $data, ASTEvaluator $evaluator): float
    {
        if (empty($arguments)) {
            return 0;
        }

        $value = $arguments[0];

        if (is_array($value)) {
            $value = array_sum(array_map('floatval', $value));
        }

        return ceil((float) $value);
    }
}
