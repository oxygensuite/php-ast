<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;
use OxygenSuite\PhpAst\Formulas\FunctionDefinition;

#[FunctionDefinition(
    signature: 'COUNT(values)',
    description: 'Number of items in an array, or 1 for a single value; a missing value or blank text counts as 0. EXISTS() tells whether a value is there, LEN() the length of a text.',
    example: 'COUNT(lines)',
    minArguments: 1,
    maxArguments: 1,
    listArgument: 0,
)]
readonly class CountFunction implements Formula
{
    use BlankTrait;

    public function execute(array $arguments, array $data, ASTEvaluator $evaluator): int
    {
        $result = $arguments[0] ?? null;

        if (is_array($result)) {
            return count($result);
        }

        return $this->isBlank($result) ? 0 : 1;
    }
}
