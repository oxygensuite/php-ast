<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTAwareFormula;
use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\FormulaEvaluator;
use OxygenSuite\PhpAst\Formulas\FormulaParser;

/**
 * MAP function: MAP(array; expression)
 * Applies an expression to each item in an array
 *
 * Uses AST-aware interface to evaluate expressions per-item
 */
readonly class MapFunction implements ASTAwareFormula
{
    public function execute(string $arguments, array $data, FormulaEvaluator $evaluator): array
    {
        $args = FormulaParser::splitArguments($arguments);

        if (count($args) < 2) {
            return [];
        }

        [$itemsExpr, $callbackExpr] = $args;

        // Evaluate the items expression to get the array
        $items = $evaluator->evaluate($itemsExpr, $data);

        if (!is_array($items)) {
            return [];
        }

        if (empty($items)) {
            return [];
        }

        // Evaluate the callback expression for each item
        return array_map(fn($item) => $evaluator->evaluate($callbackExpr, $item), $items);
    }

    public function executeWithArgs(array $arguments, array $data, ASTEvaluator $evaluator): array
    {
        // This won't work properly for callback expressions since they're pre-evaluated
        // Fallback to returning an empty array
        return [];
    }

    /**
     * Execute with raw AST nodes - evaluate callback per-item
     */
    public function executeWithASTNodes(array $astNodes, array $data, ASTEvaluator $evaluator): array
    {
        if (count($astNodes) < 2) {
            return [];
        }

        [$itemsNode, $callbackNode] = $astNodes;

        // Evaluate the items expression to get the array
        $items = $itemsNode->accept($evaluator, $data);

        if (!is_array($items)) {
            return [];
        }

        if (empty($items)) {
            return [];
        }

        // Evaluate callback AST node for each item
        return array_map(
            fn($item) => $callbackNode->accept($evaluator, $item),
            $items,
        );
    }
}
