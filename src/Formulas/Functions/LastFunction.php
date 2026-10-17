<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;
use OxygenSuite\PhpAst\Formulas\FunctionDefinition;

#[FunctionDefinition(
    signature: 'LAST(value)',
    description: 'Last item of an array, or last character of a string.',
    example: 'LAST(lines.*.line_number)',
    minArguments: 1,
    maxArguments: 1,
    listArgument: 0,
)]
readonly class LastFunction implements Formula
{
    /**
     * Returns the last element of an array or the last character of a string.
     */
    public function execute(array $arguments, array $data, ASTEvaluator $evaluator): string|float|array|int|null
    {
        if (empty($arguments)) {
            return null;
        }

        $result = $arguments[0];

        if (is_array($result)) {
            return empty($result) ? null : end($result);
        }

        return mb_substr((string) $result, -1);
    }
}
