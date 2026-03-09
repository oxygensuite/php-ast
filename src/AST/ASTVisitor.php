<?php

namespace OxygenSuite\PhpAst\AST;

/**
 * Visitor interface for traversing AST
 */
interface ASTVisitor
{
    public function visitLiteral(LiteralNode $node, array $context): mixed;
    public function visitVariable(VariableNode $node, array $context): mixed;
    public function visitFunction(FunctionNode $node, array $context): mixed;
    public function visitBinaryOp(BinaryOpNode $node, array $context): mixed;
}
