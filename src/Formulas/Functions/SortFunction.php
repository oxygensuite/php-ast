<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;
use OxygenSuite\PhpAst\Formulas\FunctionDefinition;

#[FunctionDefinition(
    signature: 'SORT(values)',
    description: 'Sorts an array ascending (or the characters of a string).',
    example: 'SORT(lines.*.item_code)',
    minArguments: 1,
    maxArguments: 1,
    listArgument: 0,
)]
readonly class SortFunction implements Formula
{
    public function execute(array $arguments, array $data, ASTEvaluator $evaluator): string|array
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
