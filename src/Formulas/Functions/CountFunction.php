<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;

readonly class CountFunction implements Formula
{
    /**
     * Syntax: COUNT(data/formula)
     */
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
