<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;

/**
 * IF function: IF(condition; trueValue; falseValue)
 */
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
