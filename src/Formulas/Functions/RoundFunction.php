<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;
use OxygenSuite\PhpAst\Formulas\FunctionDefinition;

#[FunctionDefinition(
    signature: 'ROUND(value; precision?)',
    description: 'Rounds to the given decimals (default 0); an array is summed first.',
    example: 'ROUND(summary.total_net_amount; 2)',
    minArguments: 1,
    maxArguments: 2,
)]
readonly class RoundFunction implements Formula
{
    /**
     * Example: ROUND(10.567; 2) => 10.57
     * Example: ROUND(10.567) => 11 (default precision = 0)
     */
    public function execute(array $arguments, array $data, ASTEvaluator $evaluator): float
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
