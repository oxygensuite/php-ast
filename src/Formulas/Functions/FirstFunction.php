<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;

readonly class FirstFunction implements Formula
{
    /**
     * Returns the first element of an array or the first character of a string.
     *
     * Function syntax: FIRST(data)
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
