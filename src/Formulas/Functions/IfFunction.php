<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;
use OxygenSuite\PhpAst\Formulas\FormulaEvaluator;
use OxygenSuite\PhpAst\Formulas\FormulaParser;

/**
 * IF function: IF(condition; trueValue; falseValue)
 * Single Responsibility: Conditional logic
 *
 * Note: Uses legacy execute() because conditions need special handling -
 * they may contain comparison operators that aren't evaluated by the old FormulaEvaluator
 */
readonly class IfFunction implements Formula
{
    use TruthyTrait;
    public function execute(string $arguments, array $data, FormulaEvaluator $evaluator): float|int|string|array
    {
        $parts = FormulaParser::splitArguments($arguments);

        if (count($parts) !== 3) {
            return '';
        }

        [$condition, $trueValue, $falseValue] = $parts;

        // Evaluate the condition
        $result = $evaluator->evaluate($condition, $data);

        // Return appropriate value
        return $this->evaluateCondition($result, $data, $evaluator)
            ? $evaluator->evaluate($trueValue, $data)
            : $evaluator->evaluate($falseValue, $data);
    }

    public function executeWithArgs(array $arguments, array $data, ASTEvaluator $evaluator): string|array|float|int
    {
        if (count($arguments) !== 3) {
            return '';
        }

        [$condition, $trueValue, $falseValue] = $arguments;

        // Condition is already evaluated by the AST evaluator
        // It will be a boolean, number, or comparison result
        return $this->isTruthy($condition) ? $trueValue : $falseValue;
    }

    private function evaluateCondition(mixed $condition, array $data, FormulaEvaluator $evaluator): bool
    {
        // Parse comparison operators
        if (is_string($condition) && preg_match('/^(.+?)\s*(==|!=|>=|<=|>|<|=)\s*(.+)$/', $condition, $matches)) {
            $left = $matches[1];
            $operator = $matches[2];
            $right = $matches[3];

            // Remove quotes from string literals
            $left = $evaluator->evaluate($left, $data);
            $right = $evaluator->evaluate($right, $data);

            // Evaluate as numbers if both are numeric
            if (is_numeric($left) && is_numeric($right)) {
                $left = (float) $left;
                $right = (float) $right;
            }

            return match ($operator) {
                '==', '=' => $left === $right,
                '!=' => $left !== $right,
                '>' => $left > $right,
                '<' => $left < $right,
                '>=' => $left >= $right,
                '<=' => $left <= $right,
                default => false,
            };
        }

        // No operator, check truthiness
        return $this->isTruthy($condition);
    }

}
