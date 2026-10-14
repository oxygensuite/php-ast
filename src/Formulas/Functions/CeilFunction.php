<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;
use OxygenSuite\PhpAst\Formulas\FunctionDefinition;

#[FunctionDefinition(
    signature: 'CEIL(value)',
    description: 'Rounds up; an array is summed first.',
    example: 'CEIL(summary.total_net_amount)',
    minArguments: 1,
    maxArguments: 1,
)]
readonly class CeilFunction implements Formula
{
    /**
     * Example: CEIL(10.1) => 11
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

        return ceil((float) $value);
    }
}
