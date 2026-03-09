<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\AbstractFormula;

readonly class ValueFunction extends AbstractFormula
{
    /**
     * Function syntax: VALUE(data; column)
     */
    public function executeWithArgs(array $arguments, array $data, ASTEvaluator $evaluator): string|float|array|int|null
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
