<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\ASTFormula;

/**
 * MAP function: MAP(array; expression)
 * Applies an expression to each item in an array
 */
readonly class MapFunction implements ASTFormula
{
    public function execute(array $astNodes, array $data, ASTEvaluator $evaluator): array
    {
        if (count($astNodes) < 2) {
            return [];
        }

        [$itemsNode, $callbackNode] = $astNodes;

        $items = $itemsNode->accept($evaluator, $data);

        if (!is_array($items) || empty($items)) {
            return [];
        }

        return array_map(
            fn($item) => $callbackNode->accept($evaluator, $item),
            $items,
        );
    }
}
