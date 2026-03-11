<?php

namespace OxygenSuite\PhpAst\Formulas;

use Exception;
use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\ContextProvider\ContextProvider;
use OxygenSuite\PhpAst\ContextProvider\DataResolver;
use OxygenSuite\PhpAst\ContextProvider\WildcardTransformer;
use RuntimeException;

/**
 * Main formula evaluator following Single Responsibility Principle
 */
class FormulaEvaluator
{
    private const int MAX_RECURSION_DEPTH = 32;
    private DataResolver $dataResolver;
    private int $currentDepth = 0;
    private ASTEvaluator $astEvaluator;

    public function __construct(?ContextProvider $contextProvider = null)
    {
        $this->dataResolver = new DataResolver($contextProvider);
        $this->astEvaluator = new ASTEvaluator($contextProvider);
    }

    public function evaluate(string $formula, array $data): mixed
    {
        if (++$this->currentDepth > self::MAX_RECURSION_DEPTH) {
            $this->currentDepth = 0;

            throw new RuntimeException('Formula recursion depth exceeded');
        }

        try {
            // Check if the formula contains wildcards - use ASTEvaluator directly
            if (WildcardTransformer::hasWildcards($formula)) {
                return $this->astEvaluator->evaluate($formula, $data);
            }

            // Check if it's a formula function
            [$handler, $args] = FormulaRegistry::getHandler($formula);

            if ($handler) {
                // Use AST evaluator which will call handler->execute()
                // This allows full AST parsing with FunctionNodes
                return $this->astEvaluator->evaluate($formula, $data);
            }

            // Check if the entire formula is a single quoted string (e.g. "Hello World" or 'Hello World')
            if (preg_match('/^(["\'])(?:(?!\1).)*\1$/', $formula)) {
                // Remove the outer quotes
                return substr($formula, 1, -1);
            }

            // Replace all variables with their values
            $result = $this->dataResolver->replaceVariables($formula, $data);

            if ($result === null) {
                return null;
            }

            if (is_array($result)) {
                return $result;
            }

            // Check if the formula is a simple variable reference (no operators, no functions)
            // Pattern matches: variable, variable.property, $variable, $variable.property, etc.
            $isSimpleVariable = preg_match('/^\$?[a-z_][a-z_0-9]*(?:\.[a-z_0-9]+)*$/i', $formula);

            // If the formula was a simple variable reference, and we got a result,
            // return it directly without checking for operators
            // This prevents issues like "19%" being treated as "19 % (nothing)"
            if ($isSimpleVariable && $result !== $formula) {
                return $result;
            }

            // Check if it contains operators
            if ($this->hasArithmeticOperators($result)) {
                return $this->evaluateMathExpression($result, $data);
            }

            return $result;
        } finally {
            $this->currentDepth--;
        }
    }

    /**
     * Check if the expression has arithmetic operators outside of variable references
     */
    public function hasArithmeticOperators(string $expression): bool
    {
        $withoutVars = preg_replace('/[a-z_]+(?:\.\*\.[a-z_]+)+/S', '', $expression);

        return preg_match('/[+\-*\/%><=!]|\*\*|&&|\|\||<=>|\?\?|\?:/', $withoutVars) === 1;
    }

    private function evaluateMathExpression(string $expression, array $data): mixed
    {
        // Replace all nested formulas
        $expression = FormulaParser::replaceNestedFormulas($expression, $data, $this);

        // Check if this is string concatenation (contains quoted strings with + operator specifically)
        if ($this->isStringConcatenation($expression) && str_contains($expression, '+')) {
            return $this->evaluateStringConcatenation($expression);
        }

        try {
            // Use AST evaluator for math expressions - provides caching and proper precedence
            return $this->astEvaluator->evaluate($expression, $data);
        } catch (Exception) {
            return 0;
        }
    }

    /**
     * Get AST cache statistics
     */
    public function getCacheStats(): array
    {
        return $this->astEvaluator->getCacheStats();
    }

    /**
     * Clear AST cache
     */
    public function clearCache(): void
    {
        $this->astEvaluator->clearCache();
    }

    /**
     * Check if the expression contains string concatenation
     */
    private function isStringConcatenation(string $expression): bool
    {
        return preg_match('/["\']/', $expression) === 1;
    }

    /**
     * Evaluate string concatenation expression
     */
    private function evaluateStringConcatenation(string $expression): string
    {
        // Parse the expression to find all operands and operators
        $tokens = $this->tokenizeExpression($expression);

        $result = '';
        foreach ($tokens as $token) {
            if ($token === '+') {
                continue; // Skip the + operator, we're concatenating
            }

            // Remove quotes from string literals
            $unquoted = FormulaParser::unquoteString($token);
            if ($unquoted !== $token) {
                $result .= $unquoted;
            } else {
                // It's a numeric expression or variable - evaluate it
                $trimmedToken = trim($token);

                // If it contains operators or parentheses, evaluate as math expression
                if (preg_match('/[+\-*\/()]/', $trimmedToken)) {
                    try {
                        $result .= Math::eval($trimmedToken);
                    } catch (Exception) {
                        $result .= $trimmedToken;
                    }
                } else {
                    $result .= $trimmedToken;
                }
            }
        }

        return $result;
    }

    /**
     * Tokenize an expression into operands and operators
     */
    private function tokenizeExpression(string $expression): array
    {
        $tokens = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        $depth = 0;

        $length = strlen($expression);
        for ($i = 0; $i < $length; $i++) {
            $char = $expression[$i];

            // Handle string boundaries
            if (($char === '"' || $char === "'") && ($i === 0 || $expression[$i - 1] !== '\\')) {
                if (! $inString) {
                    $inString = true;
                    $stringChar = $char;
                    $current .= $char;
                } elseif ($char === $stringChar) {
                    $inString = false;
                    $current .= $char;
                }
                continue;
            }

            if ($inString) {
                $current .= $char;
                continue;
            }

            // Handle parentheses
            if ($char === '(') {
                $depth++;
                $current .= $char;
            } elseif ($char === ')') {
                $depth--;
                $current .= $char;
            } elseif ($depth === 0 && $char === '+') {
                // Only treat + as an operator at depth 0 and outside strings
                if ($current !== '') {
                    $tokens[] = trim($current);
                    $current = '';
                }
                $tokens[] = '+';
            } else {
                $current .= $char;
            }
        }

        if ($current !== '') {
            $tokens[] = trim($current);
        }

        return $tokens;
    }
}
