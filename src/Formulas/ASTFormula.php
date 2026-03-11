<?php

namespace OxygenSuite\PhpAst\Formulas;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\AST\ASTNode;

/**
 * Interface for formula functions that need raw AST nodes.
 * Used by functions like MAP and FILTER that evaluate expressions per-item.
 */
interface ASTFormula
{
    /**
     * @param ASTNode[] $astNodes Raw AST nodes for arguments
     * @param array $data Data context
     * @param ASTEvaluator $evaluator Evaluator for evaluating AST nodes
     */
    public function execute(array $astNodes, array $data, ASTEvaluator $evaluator): mixed;
}
