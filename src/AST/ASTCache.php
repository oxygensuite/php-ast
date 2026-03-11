<?php

namespace OxygenSuite\PhpAst\AST;

/**
 * Caches parsed AST nodes by formula string
 */
class ASTCache
{
    /** @var array<string, ASTNode> */
    private array $cache = [];

    private int $maxSize;
    private int $hits = 0;
    private int $misses = 0;

    public function __construct(int $maxSize = 1000)
    {
        $this->maxSize = $maxSize;
    }

    public function get(string $formula): ?ASTNode
    {
        if (isset($this->cache[$formula])) {
            $this->hits++;

            return $this->cache[$formula];
        }

        $this->misses++;

        return null;
    }

    public function set(string $formula, ASTNode $node): void
    {
        // If full, remove the oldest entry (first key) using reset+key which is O(1)
        if (count($this->cache) >= $this->maxSize) {
            reset($this->cache);
            unset($this->cache[key($this->cache)]);
        }

        $this->cache[$formula] = $node;
    }

    public function has(string $formula): bool
    {
        return isset($this->cache[$formula]);
    }

    public function clear(): void
    {
        $this->cache = [];
        $this->hits = 0;
        $this->misses = 0;
    }

    /**
     * Get cache statistics
     *
     * @return array{hits: int, misses: int, size: int, hitRate: float}
     */
    public function getStats(): array
    {
        $total = $this->hits + $this->misses;
        $hitRate = $total > 0 ? $this->hits / $total : 0;

        return [
            'hits' => $this->hits,
            'misses' => $this->misses,
            'size' => count($this->cache),
            'hitRate' => $hitRate,
        ];
    }
}
