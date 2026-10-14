<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;
use OxygenSuite\PhpAst\Formulas\FunctionDefinition;

/**
 * SUM function: Sums all numeric values in an array or single value
 * Single Responsibility: Sum calculation
 */
#[FunctionDefinition(
    signature: 'SUM(values; …)',
    description: 'Sum of an array, or of all arguments.',
    example: 'SUM(lines.*.net_amount)',
    minArguments: 1,
    maxArguments: null,
)]
readonly class SumFunction implements Formula
{
    public function execute(array $arguments, array $data, ASTEvaluator $evaluator): float|int
    {
        if (empty($arguments)) {
            return 0;
        }

        // If single argument that's an array, sum it
        if (count($arguments) === 1 && is_array($arguments[0])) {
            return empty($arguments[0]) ? 0 : array_sum(array_map('floatval', $arguments[0]));
        }

        // If multiple arguments, sum them all
        $sum = 0;
        foreach ($arguments as $arg) {
            if (is_array($arg)) {
                $sum += array_sum(array_map('floatval', $arg));
            } elseif (is_numeric($arg)) {
                $sum += $arg;
            }
        }

        return $sum;
    }
}
