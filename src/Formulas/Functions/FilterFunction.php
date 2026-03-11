<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTAwareFormula;
use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\FormulaEvaluator;
use OxygenSuite\PhpAst\Formulas\FormulaParser;

/**
 * FILTER function: FILTER(array; condition)
 * Filters array items based on a condition expression
 *
 * Uses AST-aware interface to evaluate conditions per-item
 */
readonly class FilterFunction implements ASTAwareFormula
{
    use TruthyTrait;
    public function execute(string $arguments, array $data, FormulaEvaluator $evaluator): array
    {
        $args = FormulaParser::splitArguments($arguments);

        if (count($args) < 2) {
            return [];
        }

        [$itemsExpr, $conditionExpr] = $args;

        // Evaluate the items expression to get the array
        $items = $evaluator->evaluate($itemsExpr, $data);

        if (!is_array($items) || empty($items)) {
            return [];
        }

        // Filter items based on condition (preserve original keys)
        return array_filter($items, function ($item) use ($evaluator, $conditionExpr) {
            $result = $evaluator->evaluate($conditionExpr, $item);

            return $this->isTruthy($result);
        });
    }

    public function executeWithArgs(array $arguments, array $data, ASTEvaluator $evaluator): array
    {
        // This won't work properly for condition expressions since they're pre-evaluated
        // Fallback to returning empty array
        return [];
    }

    /**
     * Execute with raw AST nodes - evaluate condition per-item
     */
    public function executeWithASTNodes(array $astNodes, array $data, ASTEvaluator $evaluator): array
    {
        if (count($astNodes) < 2) {
            return [];
        }

        [$itemsNode, $conditionNode] = $astNodes;

        // Evaluate the items expression to get the array
        $items = $itemsNode->accept($evaluator, $data);

        if (!is_array($items) || empty($items)) {
            return [];
        }

        // Filter items based on condition AST node (preserve original keys)
        return array_filter($items, function ($item) use ($evaluator, $conditionNode) {
            $result = $conditionNode->accept($evaluator, $item);

            return $this->isTruthy($result);
        });
    }

}
