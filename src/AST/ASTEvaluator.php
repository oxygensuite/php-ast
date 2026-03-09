<?php

namespace OxygenSuite\PhpAst\AST;

use OxygenSuite\PhpAst\ContextProvider\ContextProvider;
use OxygenSuite\PhpAst\ContextProvider\DataResolver;
use OxygenSuite\PhpAst\Formulas\FormulaRegistry;
use RuntimeException;

/**
 * Evaluates AST nodes with given data context
 */
class ASTEvaluator implements ASTVisitor
{
    private const int MAX_RECURSION_DEPTH = 32;
    private int $currentDepth = 0;

    private DataResolver $dataResolver;
    private Tokenizer $tokenizer;
    private ASTCache $cache;

    public function __construct(?ContextProvider $contextProvider = null)
    {
        $this->dataResolver = new DataResolver($contextProvider);
        $this->tokenizer = new Tokenizer();
        $this->cache = new ASTCache();
    }

    /**
     * Main entry point: parse formula (with caching) and evaluate
     */
    public function evaluate(string $formula, array $data): mixed
    {
        // Get or create AST
        $ast = $this->cache->get($formula);
        if ($ast === null) {
            $ast = $this->tokenizer->tokenize($formula);
            $this->cache->set($formula, $ast);
        }

        // Evaluate AST with data context
        $this->currentDepth = 0;
        return $ast->accept($this, $data);
    }

    public function visitLiteral(LiteralNode $node, array $context): mixed
    {
        return $node->value;
    }

    public function visitVariable(VariableNode $node, array $context): mixed
    {
        return $this->dataResolver->resolveVariable($node->path, $context);
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
                throw new RuntimeException("Unknown function: $node->name");
            }

            // Check if the function needs raw AST nodes (MAP, FILTER)
            if ($handler instanceof ASTAwareFormula) {
                return $handler->executeWithASTNodes($node->arguments, $context, $this);
            }

            // For normal functions, evaluate all arguments first
            $evaluatedArgs = array_map(
                fn(ASTNode $arg) => $arg->accept($this, $context),
                $node->arguments
            );

            // Execute the function with evaluated arguments
            return $handler->executeWithArgs($evaluatedArgs, $context, $this);
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
        // Handle string concatenation
        if ($operator === '+' && (is_string($left) || is_string($right))) {
            return $left . $right;
        }

        // Comparison operators
        return match ($operator) {
            '+' => $left + $right,
            '-' => $left - $right,
            '*' => $left * $right,
            '/' => $right != 0 ? ($left / $right) : 0,
            '>' => $left > $right,
            '<' => $left < $right,
            '>=' => $left >= $right,
            '<=' => $left <= $right,
            '==', '=' => $left == $right,
            '!=' => $left != $right,
            '&&' => $left && $right,
            '||' => $left || $right,
            '%' => $right != 0 ? ($left % $right) : 0,
            '**' => pow($left, $right),
            '<=>' => $left <=> $right,
            '??' => $left ?? $right,
            '?:' => $left ?: $right,
            '!' => !($left ?? false) ? $right : $left,
            default => throw new RuntimeException("Unknown operator: $operator"),
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
    }
}
