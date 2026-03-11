<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;

readonly class ValueFunction implements Formula
{
    /**
     * Function syntax: VALUE(data; column)
     */
    public function execute(array $arguments, array $data, ASTEvaluator $evaluator): string|float|array|int|null
    {
        if (count($arguments) < 2) {
            return '';
        }

        [$source, $column] = $arguments;

        if (!is_array($source)) {
            return '';
        }

        return $source[$column] ?? '';
    }
}
