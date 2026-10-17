<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;
use OxygenSuite\PhpAst\Formulas\FunctionDefinition;

/**
 * Single Responsibility: Extract column from an array of arrays/objects
 *
 * Usage: PLUCK(lines; "quantity") - column name must be quoted
 */
#[FunctionDefinition(
    signature: 'PLUCK(array; "field")',
    description: 'Column of an array of objects; the field name must be quoted. Same as array.*.field.',
    example: 'PLUCK(lines; "quantity")',
    minArguments: 2,
    maxArguments: 2,
    listArgument: 0,
)]
readonly class PluckFunction implements Formula
{
    public function execute(array $arguments, array $data, ASTEvaluator $evaluator): array
    {
        if (count($arguments) < 2) {
            return [];
        }

        [$source, $columnKey] = $arguments;

        if (!is_array($source)) {
            return [];
        }

        // columnKey should be a string literal (quoted in the formula)
        if (!is_string($columnKey) && !is_int($columnKey)) {
            return [];
        }

        return array_column($source, $columnKey);
    }
}
