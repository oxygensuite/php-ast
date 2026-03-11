<?php

namespace OxygenSuite\PhpAst\AST;

/**
 * Represents a binary operation (+, -, *, /, >, <, ==, etc.)
 */
readonly class BinaryOpNode implements ASTNode
{
    public function __construct(public string $operator, public ASTNode $left, public ASTNode $right) {}

    public function accept(ASTVisitor $visitor, array $context): mixed
    {
        return $visitor->visitBinaryOp($this, $context);
    }
}
