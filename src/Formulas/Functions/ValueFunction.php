<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;
use OxygenSuite\PhpAst\Formulas\FunctionDefinition;

#[FunctionDefinition(
    signature: 'VALUE(object; "field")',
    description: 'A field of an object (field name quoted; empty string when missing).',
    example: 'VALUE(supplier; "name")',
    minArguments: 2,
    maxArguments: 2,
)]
readonly class ValueFunction implements Formula
{
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
