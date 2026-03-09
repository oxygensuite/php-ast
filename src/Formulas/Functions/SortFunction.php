<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\AbstractFormula;

readonly class SortFunction extends AbstractFormula
{
    public function executeWithArgs(array $arguments, array $data, ASTEvaluator $evaluator): string|array
    {
        if (empty($arguments)) {
            return [];
        }

        $result = $arguments[0];

        if (is_array($result)) {
            sort($result);
            return $result;
        }

        // sort string
        $result = mb_str_split((string) $result);
        sort($result);
        return implode('', $result);
    }
}
