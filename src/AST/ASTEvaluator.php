<?php

namespace OxygenSuite\PhpAst\AST;

use OxygenSuite\PhpAst\ContextProvider\ContextProvider;
use OxygenSuite\PhpAst\ContextProvider\DataResolver;
use OxygenSuite\PhpAst\ContextProvider\ScopeResolver;
use OxygenSuite\PhpAst\ContextProvider\WildcardResolver;
use OxygenSuite\PhpAst\Formulas\ASTFormula;
use OxygenSuite\PhpAst\Formulas\FormulaRegistry;
use OxygenSuite\PhpAst\Formulas\Functions\TruthyTrait;
use RuntimeException;

/**
 * Evaluates AST nodes with given data context
 */
class ASTEvaluator implements ASTVisitor, WildcardPathVisitor
{
    use TruthyTrait;

    private const int MAX_RECURSION_DEPTH = 32;

    /** The operators that read their operands as numbers, see toNumber() */
    private const array ARITHMETIC_OPERATORS = ['+' => true, '-' => true, '*' => true, '/' => true, '%' => true, '**' => true];
    private int $currentDepth = 0;

    private DataResolver $dataResolver;
    private Tokenizer $tokenizer;
    private ASTCache $cache;
    private ?WildcardResolver $wildcardResolver;
    private ?ScopeResolver $scopeResolver;

    /**
     * The items of MAP() and FILTER() being evaluated, innermost first, each with the path of its list
     *
     * @var list<array{item: array, path: ?string}>
     */
    private array $items = [];

    /** The data the formula is evaluated with, which a name no item of MAP() or FILTER() has is read from */
    private array $data = [];

    /** The paths a WildcardResolver gave that still have wildcards, parsed, apart from the formulas */
    private ASTCache $resolvedPaths;

    public function __construct(?ContextProvider $contextProvider = null)
    {
        $this->dataResolver = new DataResolver($contextProvider);
        $this->tokenizer = new Tokenizer();
        $this->cache = new ASTCache();
        $this->wildcardResolver = $contextProvider instanceof WildcardResolver ? $contextProvider : null;
        $this->scopeResolver = $contextProvider instanceof ScopeResolver ? $contextProvider : null;
        $this->resolvedPaths = new ASTCache(100);
    }

    /**
     * Main entry point: parse formula (with caching) and evaluate
     */
    public function evaluate(string $formula, array $data): mixed
    {
        $previous = $this->data;
        $this->data = $data;

        try {
            // A plain path, such as "summary.total_net_amount", is read as the variable it parses to, without parsing it
            $path = trim($formula);
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/', $path)) {
                return $this->variable($path, $data);
            }

            // Evaluate AST with data context
            $this->currentDepth = 0;

            return $this->parse($this->cache, $formula)->accept($this, $data);
        } finally {
            $this->data = $previous;
        }
    }

    public function visitLiteral(LiteralNode $node, array $context): mixed
    {
        return $node->value;
    }

    public function visitVariable(VariableNode $node, array $context): mixed
    {
        return $this->variable($node->path, $context);
    }

    /**
     * Evaluates a node once for an item of a list, as MAP() and FILTER() do: a name the item has reads the item, and
     * any other the scopes outside it
     *
     * @param ?string $path The path of the item's list as the formula writes it, see listPath()
     */
    public function evaluateItem(ASTNode $node, array $item, ?string $path): mixed
    {
        array_unshift($this->items, ['item' => $item, 'path' => $path]);

        try {
            return $node->accept($this, $item);
        } finally {
            array_shift($this->items);
        }
    }

    /**
     * The path of the list a node gives, as the formula writes it, when it is a path or the items of one kept by a
     * function that keeps its list's items: "taxes" for taxes and FILTER(taxes; amount > 0)
     */
    public function listPath(ASTNode $node): ?string
    {
        if ($node instanceof VariableNode || $node instanceof WildcardPathNode) {
            return $node->path;
        }

        if (! $node instanceof FunctionNode || $node->arguments === []) {
            return null;
        }

        $handler = FormulaRegistry::getHandlerByName($node->name);

        return $handler !== null && FormulaRegistry::definitionOf($handler)->keepsItems ? $this->listPath($node->arguments[0]) : null;
    }

    /**
     * The value of a name, as a ScopeResolver finds it. Without one: of the innermost item of MAP() or FILTER() that
     * has it, else the context provider's, else the data's
     */
    private function variable(string $path, array $context): mixed
    {
        if ($this->scopeResolver !== null) {
            return $this->scopeResolver->resolveName($path, $this->items);
        }

        $name = explode('.', $path, 2)[0];
        foreach ($this->items as $scope) {
            if (array_key_exists($name, $scope['item'])) {
                return $this->dataResolver->resolve($scope['item'], $path);
            }
        }

        return $this->dataResolver->resolveVariable($path, $this->items === [] ? $context : $this->data);
    }

    /**
     * Every item the path reaches, or the items of the path the WildcardResolver gives instead
     */
    public function visitWildcardPath(WildcardPathNode $node, array $context): mixed
    {
        $path = $node->ofItem ? null : $this->wildcardResolver?->resolveWildcardPath($node->path, $node->asList);

        if ($path === null || $path === $node->path) {
            return $node->expansion->accept($this, $context);
        }

        if (! str_contains($path, '*')) {
            return $this->variable($path, $context);
        }

        $resolved = $this->parse($this->resolvedPaths, $path);

        return ($resolved instanceof WildcardPathNode ? $resolved->expansion : $resolved)->accept($this, $context);
    }

    /**
     * The formula's AST, parsed once and kept in the cache
     */
    private function parse(ASTCache $cache, string $formula): ASTNode
    {
        $ast = $cache->get($formula);
        if ($ast === null) {
            $ast = $this->tokenizer->tokenize($formula);
            $cache->set($formula, $ast);
        }

        return $ast;
    }

    public function visitFunction(FunctionNode $node, array $context): mixed
    {
        if (++$this->currentDepth > self::MAX_RECURSION_DEPTH) {
            $this->currentDepth = 0;

            throw new RuntimeException('Formula recursion depth exceeded');
        }

        try {
            FormulaRegistry::ensureInitialized();

            // Get the handler for this function
            $handler = FormulaRegistry::getHandlerByName($node->name);
            if ($handler === null) {
                throw new RuntimeException('Unknown function: ' . strtoupper($node->name));
            }

            // Check if the function needs raw AST nodes (MAP, FILTER)
            if ($handler instanceof ASTFormula) {
                return $handler->execute($node->arguments, $context, $this);
            }

            // For normal functions, evaluate all arguments first
            $evaluatedArgs = array_map(
                fn(ASTNode $arg) => $arg->accept($this, $context),
                $node->arguments,
            );

            // Execute the function with evaluated arguments
            return $handler->execute($evaluatedArgs, $context, $this);
        } finally {
            $this->currentDepth--;
        }
    }

    public function visitBinaryOp(BinaryOpNode $node, array $context): mixed
    {
        if (++$this->currentDepth > self::MAX_RECURSION_DEPTH) {
            $this->currentDepth = 0;

            throw new RuntimeException('Formula recursion depth exceeded');
        }

        try {
            $left = $node->left->accept($this, $context);
            $right = $node->right->accept($this, $context);

            return $this->evaluateBinaryOperation($node->operator, $left, $right);
        } finally {
            $this->currentDepth--;
        }
    }

    private function evaluateBinaryOperation(string $operator, mixed $left, mixed $right): mixed
    {
        if ($operator === '+' && ! $this->adds($left, $right)) {
            return $left . $right;
        }

        if (isset(self::ARITHMETIC_OPERATORS[$operator])) {
            $left = $this->toNumber($left, $operator);
            $right = $this->toNumber($right, $operator);
        }

        return match ($operator) {
            '+' => $left + $right,
            '-' => $left - $right,
            '*' => $left * $right,
            '/' => $right !== 0 && $right !== 0.0 ? ($left / $right) : 0,
            '>' => $left > $right,
            '<' => $left < $right,
            '>=' => $left >= $right,
            '<=' => $left <= $right,
            '==', '=' => $left == $right,
            '!=' => $left != $right,
            '&&' => $this->isTruthy($left) && $this->isTruthy($right),
            '||' => $this->isTruthy($left) || $this->isTruthy($right),
            '%' => $right !== 0 && $right !== 0.0 ? ($left % $right) : 0,
            '**' => abs($right) > 1000 ? throw new RuntimeException('Exponent exceeds maximum allowed value') : pow($left, $right),
            '<=>' => $left <=> $right,
            '??' => $left ?? $right,
            '?:' => $this->isTruthy($left) ? $left : $right,
            default => throw new RuntimeException("Unknown operator: $operator"),
        };
    }

    /**
     * Whether "+" adds rather than joins: it joins text, so codes such as "001" + "12" keep their digits, and adds
     * when a number meets a number or a number written as text, such as uid "1" + 5.
     */
    private function adds(mixed $left, mixed $right): bool
    {
        if (is_string($left) && is_string($right)) {
            return false;
        }

        if (is_string($left)) {
            return is_numeric($left) && (is_int($right) || is_float($right));
        }

        if (is_string($right)) {
            return is_numeric($right) && (is_int($left) || is_float($left));
        }

        return true;
    }

    /**
     * A number written as text, such as "0" or "12.5", as that number, and nothing as 0, so the zero checks of "/"
     * and "%" see them; any other value as it is.
     *
     * @throws RuntimeException When the value is text that is not a number, such as "ΟΔΟΣ ΕΡΜΟΥ" or "24%"
     */
    private function toNumber(mixed $value, string $operator): mixed
    {
        return match (true) {
            is_string($value) && is_numeric($value) => $value + 0,
            is_string($value) => throw new RuntimeException("'{$operator}' needs numbers, got the text '{$value}'"),
            $value === null, is_bool($value) => (int) $value,
            default => $value,
        };
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        return $this->cache->getStats();
    }

    /**
     * Clear the AST cache
     */
    public function clearCache(): void
    {
        $this->cache->clear();
        $this->resolvedPaths->clear();
    }
}
