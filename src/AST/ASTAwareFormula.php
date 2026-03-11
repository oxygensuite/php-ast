<?php

namespace OxygenSuite\PhpAst\AST;

use OxygenSuite\PhpAst\Formulas\Formula;

/**
 * Interface for formulas that need access to raw AST nodes
 * Used by MAP and FILTER which need to evaluate expressions per-item
 */
interface ASTAwareFormula extends Formula
{
    /**
     * Execute with raw AST nodes (not pre-evaluated)
     *
     * @param ASTNode[] $astNodes Raw AST nodes for arguments
     * @param array $data Data context
     * @param ASTEvaluator $evaluator Evaluator for evaluating AST nodes
     *
     * @return mixed
     */
    public function executeWithASTNodes(array $astNodes, array $data, ASTEvaluator $evaluator): mixed;
}
