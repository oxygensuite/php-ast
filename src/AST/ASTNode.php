<?php

namespace OxygenSuite\PhpAst\AST;

/**
 * Base interface for all AST nodes
 */
interface ASTNode
{
    /**
     * Accept a visitor for evaluation or transformation
     */
    public function accept(ASTVisitor $visitor, array $context): mixed;
}
