<?php

namespace OxygenSuite\PhpAst\AST;

/**
 * Represents a function call (e.g., IF, SUM, PLUCK)
 */
readonly class FunctionNode implements ASTNode
{
    /**
     * @param string $name Function name (uppercase)
     * @param ASTNode[] $arguments Array of argument nodes
     */
    public function __construct(public string $name, public array $arguments)
    {
    }

    public function accept(ASTVisitor $visitor, array $context): mixed
    {
        return $visitor->visitFunction($this, $context);
    }
}
