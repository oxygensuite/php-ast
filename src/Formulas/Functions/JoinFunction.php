<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\AbstractFormula;

readonly class JoinFunction extends AbstractFormula
{
    /**
     * Syntax: JOIN(data; separator)
     */
    public function executeWithArgs(array $arguments, array $data, ASTEvaluator $evaluator): string|array
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
