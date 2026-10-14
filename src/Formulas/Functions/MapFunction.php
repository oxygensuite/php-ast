<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\ASTFormula;
use OxygenSuite\PhpAst\Formulas\FunctionDefinition;

/**
 * Applies an expression to each item in an array
 */
#[FunctionDefinition(
    signature: 'MAP(array; expression)',
    description: 'Evaluates the expression per item; use the item\'s short field names.',
    example: 'SUM(MAP(lines; quantity * unit_price))',
    minArguments: 2,
    maxArguments: 2,
    itemArgument: 1,
)]
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
