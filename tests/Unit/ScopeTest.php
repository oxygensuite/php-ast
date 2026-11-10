<?php

namespace Tests\Unit;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\ContextProvider\ContextProvider;
use OxygenSuite\PhpAst\ContextProvider\ScopeResolver;
use Tests\TestCase;

class ScopeTest extends TestCase
{
    public const array DATA = [
        'rate' => 2,
        'amount' => 1000,
        'lines' => [
            ['amount' => 10, 'taxes' => [['amount' => 1], ['amount' => 2]]],
            ['amount' => 20, 'taxes' => [['amount' => 3]]],
        ],
    ];

    public function test_an_item_of_map_hides_the_same_name_of_the_context(): void
    {
        $evaluator = new ASTEvaluator($this->context(['amount' => 500]));

        $this->assertSame([10, 20], $evaluator->evaluate('MAP(lines; amount)', self::DATA));
    }

    public function test_a_name_an_item_does_not_have_reads_the_context_then_the_data(): void
    {
        $evaluator = new ASTEvaluator($this->context(['factor' => 3]));

        $this->assertSame([60, 120], $evaluator->evaluate('MAP(lines; amount * factor * rate)', self::DATA));
    }

    public function test_nested_items_read_their_own_names_first(): void
    {
        $evaluator = new ASTEvaluator();

        $this->assertEquals([13, 23], $evaluator->evaluate('MAP(lines; amount + SUM(MAP(taxes; amount)))', self::DATA));
    }

    public function test_filter_reads_its_items_first(): void
    {
        $evaluator = new ASTEvaluator($this->context(['amount' => 0]));

        $this->assertCount(1, $evaluator->evaluate('FILTER(lines; amount > 15)', self::DATA));
    }

    public function test_a_scope_resolver_decides_where_a_name_is_and_is_told_the_items_and_their_lists(): void
    {
        $resolver = new class implements ContextProvider, ScopeResolver {
            /** @var list<array{string, list<?string>}> */
            public array $asked = [];

            public function getContextValue(string $key): mixed
            {
                return null;
            }

            public function resolveName(string $path, array $items): mixed
            {
                $this->asked[] = [$path, array_column($items, 'path')];

                // The tax has no "code"; the rest is read from the data
                return match ($path) {
                    'code' => null,
                    'amount' => $items[0]['item']['amount'],
                    'rate' => 'R',
                    default => ['lines' => ScopeTest::DATA['lines'], 'taxes' => [['amount' => 1], ['amount' => 2], ['amount' => 3]]][$path] ?? null,
                };
            }
        };

        $evaluator = new ASTEvaluator($resolver);

        $this->assertSame(['2R', '3R'], array_values($evaluator->evaluate('MAP(FILTER(taxes; amount > 1); CONCAT(code; amount; rate))', [])));
        $this->assertContains(['code', ['taxes']], $resolver->asked);

        $evaluator->evaluate('MAP(SORT(lines); code)', []);
        $this->assertContains(['code', ['lines']], $resolver->asked);
    }

    private function context(array $values): ContextProvider
    {
        return new readonly class ($values) implements ContextProvider {
            public function __construct(private array $values) {}

            public function getContextValue(string $key): mixed
            {
                return $this->values[$key] ?? null;
            }
        };
    }
}
