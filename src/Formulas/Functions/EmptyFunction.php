<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;
use OxygenSuite\PhpAst\Formulas\FunctionDefinition;

#[FunctionDefinition(
    signature: 'EMPTY(value)',
    description: 'Whether the value is not there: missing, blank text or an empty list. Zero is a value, so it is not empty.',
    example: 'EMPTY(customer.name)',
    minArguments: 1,
    maxArguments: 1,
)]
readonly class EmptyFunction implements Formula
{
    use BlankTrait;

    public function execute(array $arguments, array $data, ASTEvaluator $evaluator): bool
    {
        return $this->isBlank($arguments[0] ?? null);
    }
}
