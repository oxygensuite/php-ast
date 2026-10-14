<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;
use OxygenSuite\PhpAst\Formulas\FunctionDefinition;

#[FunctionDefinition(
    signature: 'MAX(values; …)',
    description: 'Largest number of an array, or of all arguments.',
    example: 'MAX(lines.*.unit_price)',
    minArguments: 1,
    maxArguments: null,
)]
readonly class MaxFunction implements Formula
{
    public function execute(array $arguments, array $data, ASTEvaluator $evaluator): float
    {
        if (empty($arguments)) {
            return 0;
        }

        // If a single argument that's an array, find max
        if (count($arguments) === 1 && is_array($arguments[0])) {
            return empty($arguments[0]) ? 0 : max(array_map('floatval', $arguments[0]));
        }

        // If multiple arguments, find max of all
        $values = [];
        foreach ($arguments as $arg) {
            if (is_array($arg)) {
                array_push($values, ...array_map('floatval', $arg));
            } elseif (is_numeric($arg)) {
                $values[] = (float) $arg;
            }
        }

        return empty($values) ? 0 : max($values);
    }
}
