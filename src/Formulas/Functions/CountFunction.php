<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;
use OxygenSuite\PhpAst\Formulas\FunctionDefinition;

#[FunctionDefinition(
    signature: 'COUNT(values)',
    description: 'Number of items in an array; any other value counts as 1, a missing one included, and only an empty string as 0.',
    example: 'COUNT(lines)',
    minArguments: 1,
    maxArguments: 1,
    listArgument: 0,
)]
readonly class CountFunction implements Formula
{
    public function execute(array $arguments, array $data, ASTEvaluator $evaluator): int
    {
        if (empty($arguments)) {
            return 0;
        }

        $result = $arguments[0];

        if (is_array($result)) {
            return count($result);
        }

        return is_numeric($result) || $result !== '' ? 1 : 0;
    }
}
