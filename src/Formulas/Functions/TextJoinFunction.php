<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;
use OxygenSuite\PhpAst\Formulas\FunctionDefinition;
use RuntimeException;

#[FunctionDefinition(
    signature: 'TEXTJOIN(separator; value; …)',
    description: 'Joins the values that are not blank as text, with the separator between them, a list item after item; unlike JOIN(list; separator), the separator comes first.',
    example: 'TEXTJOIN(" "; header.series; header.invoice_number)',
    minArguments: 2,
    maxArguments: null,
)]
readonly class TextJoinFunction implements Formula
{
    use BlankTrait;
    use TextValuesTrait;

    /**
     * @throws RuntimeException When an argument holds a list or a record, or the separator is a list
     */
    public function execute(array $arguments, array $data, ASTEvaluator $evaluator): string
    {
        $separator = array_shift($arguments);
        $texts = array_filter($this->textValues($arguments, 'TEXTJOIN', firstPosition: 2), fn(string $text) => ! $this->isBlank($text));

        return implode($this->text($separator, 'TEXTJOIN', 1), $texts);
    }
}
