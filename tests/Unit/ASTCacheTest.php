<?php

namespace Tests\Unit;

use OxygenSuite\PhpAst\AST\ASTCache;
use OxygenSuite\PhpAst\AST\LiteralNode;
use Tests\TestCase;

class ASTCacheTest extends TestCase
{
    public function test_a_full_cache_removes_the_least_recently_used_formula()
    {
        $cache = new ASTCache(maxSize: 2);
        $cache->set('reused', new LiteralNode(1));
        $cache->set('once', new LiteralNode(2));

        $cache->get('reused');
        $cache->set('new', new LiteralNode(3));

        $this->assertTrue($cache->has('reused'));
        $this->assertFalse($cache->has('once'));
        $this->assertTrue($cache->has('new'));
    }
}
