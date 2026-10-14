<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\ASTFormula;
use OxygenSuite\PhpAst\Formulas\FunctionDefinition;

/**
 * Filters array items based on a condition expression
 */
#[FunctionDefinition(
    signature: 'FILTER(array; condition)',
    description: 'Items of the array for which the condition, evaluated per item, is truthy.',
    example: 'FILTER(lines; quantity > 0)',
    minArguments: 2,
    maxArguments: 2,
    itemArgument: 1,
)]
readonly class FilterFunction implements ASTFormula
{
    use TruthyTrait;

    public function execute(array $astNodes, array $data, ASTEvaluator $evaluator): array
    {
        if (count($astNodes) < 2) {
            return [];
        }

        [$itemsNode, $conditionNode] = $astNodes;

        $items = $itemsNode->accept($evaluator, $data);

        if (!is_array($items) || empty($items)) {
            return [];
        }

        return array_filter($items, function ($item) use ($evaluator, $conditionNode) {
            $result = $conditionNode->accept($evaluator, $item);

            return $this->isTruthy($result);
        });
    }
}
