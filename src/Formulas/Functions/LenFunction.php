<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;
use OxygenSuite\PhpAst\Formulas\FunctionDefinition;
use RuntimeException;

#[FunctionDefinition(
    signature: 'LEN(text)',
    description: 'Number of characters of the text; a number counts the characters of its value, not of how it prints (5 for 173.50); a missing value has 0. COUNT() counts the items of a list.',
    example: 'LEN(header.invoice_number)',
    minArguments: 1,
    maxArguments: 1,
)]
readonly class LenFunction implements Formula
{
    /**
     * @throws RuntimeException When the value is a list or a record, whose items COUNT() counts
     */
    public function execute(array $arguments, array $data, ASTEvaluator $evaluator): int
    {
        $value = $arguments[0] ?? null;

        if (is_array($value) || is_object($value)) {
            throw new RuntimeException('LEN() measures text and numbers; COUNT() counts the items of a list or a record');
        }

        return mb_strlen((string) $value);
    }
}
