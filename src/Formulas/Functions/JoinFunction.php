<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;

readonly class JoinFunction implements Formula
{
    /**
     * Syntax: JOIN(data; separator)
     */
    public function execute(array $arguments, array $data, ASTEvaluator $evaluator): string|array
    {
        if (count($arguments) < 2) {
            return [];
        }

        [$source, $separator] = $arguments;

        // Ensure source is an array
        $source = is_array($source) ? $source : [$source];

        return join((string) $separator, $source);
    }
}
