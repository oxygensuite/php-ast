<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;
use OxygenSuite\PhpAst\Formulas\FunctionDefinition;

#[FunctionDefinition(
    signature: 'IF(condition; then; else)',
    description: 'then when the condition is truthy, otherwise else.',
    example: 'IF(header.currency == "EUR"; "€"; header.currency)',
    minArguments: 3,
    maxArguments: 3,
)]
readonly class IfFunction implements Formula
{
    use TruthyTrait;

    public function execute(array $arguments, array $data, ASTEvaluator $evaluator): string|array|float|int|null
    {
        if (count($arguments) !== 3) {
            return '';
        }

        [$condition, $trueValue, $falseValue] = $arguments;

        return $this->isTruthy($condition) ? $trueValue : $falseValue;
    }
}
