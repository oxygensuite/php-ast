<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;
use OxygenSuite\PhpAst\Formulas\FunctionDefinition;

#[FunctionDefinition(
    signature: 'JOIN(values; separator)',
    description: 'Joins array items with a separator.',
    example: 'JOIN(lines.*.item_code; ", ")',
    minArguments: 2,
    maxArguments: 2,
    listArgument: 0,
)]
readonly class JoinFunction implements Formula
{
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
