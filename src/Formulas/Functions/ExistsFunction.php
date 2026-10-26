<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;
use OxygenSuite\PhpAst\Formulas\FunctionDefinition;

#[FunctionDefinition(
    signature: 'EXISTS(value)',
    description: 'Whether the value is there, the opposite of EMPTY(): not missing, blank text or an empty list. Zero is a value, so it exists.',
    example: 'EXISTS(header.series)',
    minArguments: 1,
    maxArguments: 1,
)]
readonly class ExistsFunction implements Formula
{
    use BlankTrait;

    public function execute(array $arguments, array $data, ASTEvaluator $evaluator): bool
    {
        return ! $this->isBlank($arguments[0] ?? null);
    }
}
