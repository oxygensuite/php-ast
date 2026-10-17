<?php

namespace Tests\Unit;

use OxygenSuite\PhpAst\AST\ASTVisitor;
use OxygenSuite\PhpAst\AST\BinaryOpNode;
use OxygenSuite\PhpAst\AST\FunctionNode;
use OxygenSuite\PhpAst\AST\LiteralNode;
use OxygenSuite\PhpAst\AST\Tokenizer;
use OxygenSuite\PhpAst\AST\VariableNode;
use OxygenSuite\PhpAst\ContextProvider\ContextProvider;
use OxygenSuite\PhpAst\ContextProvider\WildcardResolver;
use OxygenSuite\PhpAst\Formulas\FormulaEvaluator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WildcardResolverTest extends TestCase
{
    private const array DATA = [
        'lines' => [
            ['quantity' => 2, 'item_code' => 'A', 'taxes' => [['amount' => 1], ['amount' => 2]]],
            ['quantity' => 5, 'item_code' => 'B', 'taxes' => [['amount' => 10], ['amount' => 20]]],
        ],
    ];

    /**
     * @return array<string, array{string, list<array{string, bool}>}>
     */
    public static function wildcardPaths(): array
    {
        return [
            'a path' => ['lines.*.quantity', [['lines.*.quantity', false]]],
            'the only argument of SUM' => ['SUM(lines.*.quantity)', [['lines.*.quantity', true]]],
            'one of the values SUM adds' => ['SUM(lines.*.quantity; 1)', [['lines.*.quantity', false]]],
            'the list COUNT counts' => ['COUNT(lines.*.taxes)', [['lines.*.taxes', true]]],
            'the list JOIN joins' => ['JOIN(lines.*.item_code; ",")', [['lines.*.item_code', true]]],
            'a condition of IF' => ['IF(COUNT(lines.*.quantity) > 1; 1; 0)', [['lines.*.quantity', true]]],
            'an argument of a call inside SUM' => ['SUM(IF(1; lines.*.quantity; 0))', [['lines.*.quantity', false]]],
            'two paths' => ['CONCAT(lines.*.item_code; lines.*.quantity)', [['lines.*.item_code', false], ['lines.*.quantity', false]]],
            'no wildcard' => ['SUM(MAP(lines; quantity))', []],
            'a path of the item FILTER tests' => ['COUNT(FILTER(lines; COUNT(taxes.*.amount) > 1))', []],
            'a path of the item MAP maps' => ['MAP(lines; SUM(taxes.*.amount))', []],
        ];
    }

    /**
     * @param list<array{string, bool}> $expected
     */
    #[DataProvider('wildcardPaths')]
    public function test_the_resolver_is_asked_for_each_wildcard_path_and_whether_it_is_read_as_a_list(string $formula, array $expected)
    {
        $resolver = $this->resolver(fn() => null);

        (new FormulaEvaluator($resolver))->evaluate($formula, self::DATA);

        $this->assertSame($expected, $resolver->asked);
    }

    public function test_a_path_the_resolver_gives_is_read_instead()
    {
        $resolver = $this->resolver(fn(string $path) => str_replace('lines.*', 'lines.1', $path));

        $evaluator = new FormulaEvaluator($resolver);

        $this->assertSame(10, $evaluator->evaluate('lines.*.quantity * 2', self::DATA));
        $this->assertSame('B5', $evaluator->evaluate('CONCAT(lines.*.item_code; lines.*.quantity)', self::DATA));
        $this->assertEquals(30, $evaluator->evaluate('SUM(lines.*.taxes.*.amount)', self::DATA));
    }

    public function test_a_wildcard_path_reads_all_items_when_the_resolver_gives_nothing()
    {
        $evaluator = new FormulaEvaluator($this->resolver(fn() => null));

        $this->assertSame([2, 5], $evaluator->evaluate('lines.*.quantity', self::DATA));
        $this->assertEquals(33, $evaluator->evaluate('SUM(lines.*.taxes.*.amount)', self::DATA));
    }

    public function test_a_visitor_without_wildcard_paths_visits_their_expansion()
    {
        $visitor = new class implements ASTVisitor {
            public function visitLiteral(LiteralNode $node, array $context): mixed
            {
                return $node->value;
            }

            public function visitVariable(VariableNode $node, array $context): mixed
            {
                return $node->path;
            }

            public function visitFunction(FunctionNode $node, array $context): mixed
            {
                return $node->name . '(' . implode('; ', array_map(fn($argument) => $argument->accept($this, $context), $node->arguments)) . ')';
            }

            public function visitBinaryOp(BinaryOpNode $node, array $context): mixed
            {
                return $node->operator;
            }
        };

        $this->assertSame('PLUCK(lines; quantity)', (new Tokenizer())->tokenize('lines.*.quantity')->accept($visitor, []));
    }

    /**
     * @param \Closure(string, bool): ?string $resolve
     */
    private function resolver(\Closure $resolve): ContextProvider&WildcardResolver
    {
        return new class ($resolve) implements ContextProvider, WildcardResolver {
            /** @var list<array{string, bool}> */
            public array $asked = [];

            public function __construct(private readonly \Closure $resolve) {}

            public function getContextValue(string $key): mixed
            {
                return null;
            }

            public function resolveWildcardPath(string $path, bool $asList): ?string
            {
                $this->asked[] = [$path, $asList];

                return ($this->resolve)($path, $asList);
            }
        };
    }
}
