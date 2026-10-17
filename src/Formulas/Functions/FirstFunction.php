<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;
use OxygenSuite\PhpAst\Formulas\FunctionDefinition;

#[FunctionDefinition(
    signature: 'FIRST(value)',
    description: 'First item of an array, or first character of a string.',
    example: 'FIRST(lines.*.item_description)',
    minArguments: 1,
    maxArguments: 1,
    listArgument: 0,
)]
readonly class FirstFunction implements Formula
{
    /**
     * Returns the first element of an array or the first character of a string.
     */
    public function execute(array $arguments, array $data, ASTEvaluator $evaluator): string|float|array|int|null
    {
        if (empty($arguments)) {
            return null;
        }

        $result = $arguments[0];

        if (is_array($result)) {
            return empty($result) ? null : reset($result);
        }

        return mb_substr((string) $result, 0, 1);
    }
}
