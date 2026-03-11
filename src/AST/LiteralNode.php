<?php

namespace OxygenSuite\PhpAst\AST;

/**
 * Represents a literal value (string, number, boolean, null)
 */
readonly class LiteralNode implements ASTNode
{
    public function __construct(public string|int|float|bool|null $value) {}

    public function accept(ASTVisitor $visitor, array $context): mixed
    {
        return $visitor->visitLiteral($this, $context);
    }
}
