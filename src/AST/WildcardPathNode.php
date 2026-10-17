<?php

namespace OxygenSuite\PhpAst\AST;

/**
 * A path with wildcards (e.g., "lines.*.quantity"), kept as written next to its expansion into PLUCK() calls, which
 * reads every item, so a WildcardResolver can choose the items it reads
 */
readonly class WildcardPathNode implements ASTNode
{
    /**
     * @param bool $asList Whether a function reads it as its list, as SUM(lines.*.net_amount) does
     * @param bool $ofItem Whether it is read from each item of a list, as in FILTER(lines; COUNT(taxes.*.amount) > 0)
     */
    public function __construct(public string $path, public ASTNode $expansion, public bool $asList = false, public bool $ofItem = false) {}

    public function readAsList(): self
    {
        return new self($this->path, $this->expansion, true, $this->ofItem);
    }

    public function accept(ASTVisitor $visitor, array $context): mixed
    {
        return $visitor instanceof WildcardPathVisitor
            ? $visitor->visitWildcardPath($this, $context)
            : $this->expansion->accept($visitor, $context);
    }
}
