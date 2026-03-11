<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;

readonly class LastFunction implements Formula
{
    /**
     * Returns the last element of an array or the last character of a string.
     *
     * Function syntax: LAST(data)
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
