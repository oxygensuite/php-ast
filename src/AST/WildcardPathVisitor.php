<?php

namespace OxygenSuite\PhpAst\AST;

/**
 * Implemented by an ASTVisitor that visits wildcard paths themselves; any other visitor visits their expansion
 */
interface WildcardPathVisitor
{
    public function visitWildcardPath(WildcardPathNode $node, array $context): mixed;
}
