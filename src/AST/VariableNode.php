<?php

namespace OxygenSuite\PhpAst\AST;

/**
 * Represents a variable reference (e.g., "line.quantity", "invoice.total")
 */
readonly class VariableNode implements ASTNode
{
    public function __construct(public string $path) {}

    public function accept(ASTVisitor $visitor, array $context): mixed
    {
        return $visitor->visitVariable($this, $context);
    }
}
