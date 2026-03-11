<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\ASTFormula;

/**
 * FILTER function: FILTER(array; condition)
 * Filters array items based on a condition expression
 */
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
