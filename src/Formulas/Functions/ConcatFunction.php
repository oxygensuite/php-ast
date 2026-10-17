<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;
use OxygenSuite\PhpAst\Formulas\FunctionDefinition;
use RuntimeException;

#[FunctionDefinition(
    signature: 'CONCAT(value; …)',
    description: 'Joins the values as text, a list item after item; a missing value adds nothing.',
    example: 'CONCAT(header.series; "-"; header.invoice_number)',
    minArguments: 1,
    maxArguments: null,
)]
readonly class ConcatFunction implements Formula
{
    /**
     * @throws RuntimeException When an argument holds a list or a record, which has no text of its own
     */
    public function execute(array $arguments, array $data, ASTEvaluator $evaluator): string
    {
        $text = '';

        foreach ($arguments as $index => $argument) {
            foreach (is_array($argument) ? $argument : [$argument] as $value) {
                if (is_array($value) || is_object($value)) {
                    throw new RuntimeException(sprintf('CONCAT() joins text and numbers; argument %d holds a list or a record', $index + 1));
                }

                $text .= (string) $value;
            }
        }

        return $text;
    }
}
