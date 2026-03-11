<?php

namespace OxygenSuite\PhpAst\AST;

use OxygenSuite\PhpAst\ContextProvider\WildcardTransformer;
use OxygenSuite\PhpAst\Formulas\FormulaRegistry;
use RuntimeException;

/**
 * Tokenizes formula strings into AST
 */
class Tokenizer
{
    private const int MAX_FORMULA_LENGTH = 10_000;

    private int $position = 0;
    private string $input = '';
    private int $length = 0;

    public function tokenize(string $formula): ASTNode
    {
        if (strlen($formula) > self::MAX_FORMULA_LENGTH) {
            throw new RuntimeException('Formula exceeds maximum allowed length');
        }

        FormulaRegistry::ensureInitialized();

        $this->input = trim($formula);
        $this->length = strlen($this->input);
        $this->position = 0;

        return $this->parseExpression();
    }

    private function parseExpression(): ASTNode
    {
        return $this->parseNullCoalescing();
    }

    private function parseNullCoalescing(): ASTNode
    {
        $left = $this->parseLogicalOr();

        while (!$this->isAtEnd()) {
            $this->skipWhitespace();
            $operator = $this->peekOperator();

            if (!in_array($operator, ['??', '?:'])) {
                break;
            }

            $this->consumeOperator($operator);
            $this->skipWhitespace();

            $right = $this->parseLogicalOr();
            $left = new BinaryOpNode($operator, $left, $right);
        }

        return $left;
    }

    private function parseLogicalOr(): ASTNode
    {
        $left = $this->parseLogicalAnd();

        while (!$this->isAtEnd()) {
            $this->skipWhitespace();
            $operator = $this->peekOperator();

            if ($operator !== '||') {
                break;
            }

            $this->consumeOperator($operator);
            $this->skipWhitespace();

            $right = $this->parseLogicalAnd();
            $left = new BinaryOpNode($operator, $left, $right);
        }

        return $left;
    }

    private function parseLogicalAnd(): ASTNode
    {
        $left = $this->parseComparison();

        while (!$this->isAtEnd()) {
            $this->skipWhitespace();
            $operator = $this->peekOperator();

            if ($operator !== '&&') {
                break;
            }

            $this->consumeOperator($operator);
            $this->skipWhitespace();

            $right = $this->parseComparison();
            $left = new BinaryOpNode($operator, $left, $right);
        }

        return $left;
    }

    private function parseComparison(): ASTNode
    {
        $left = $this->parseAddition();

        while (!$this->isAtEnd()) {
            $this->skipWhitespace();
            $operator = $this->peekOperator();

            if (!in_array($operator, ['==', '!=', '<=', '>=', '<', '>', '=', '<=>'])) {
                break;
            }

            $this->consumeOperator($operator);
            $this->skipWhitespace();

            $right = $this->parseAddition();
            $left = new BinaryOpNode($operator, $left, $right);
        }

        return $left;
    }

    private function parseAddition(): ASTNode
    {
        $left = $this->parseMultiplication();

        while (!$this->isAtEnd()) {
            $this->skipWhitespace();
            $operator = $this->peekOperator();

            if (!in_array($operator, ['+', '-'])) {
                break;
            }

            $this->consumeOperator($operator);
            $this->skipWhitespace();

            $right = $this->parseMultiplication();
            $left = new BinaryOpNode($operator, $left, $right);
        }

        return $left;
    }

    private function parseMultiplication(): ASTNode
    {
        $left = $this->parseExponentiation();

        while (!$this->isAtEnd()) {
            $this->skipWhitespace();
            $operator = $this->peekOperator();

            if (!in_array($operator, ['*', '/', '%'])) {
                break;
            }

            $this->consumeOperator($operator);
            $this->skipWhitespace();

            $right = $this->parseExponentiation();
            $left = new BinaryOpNode($operator, $left, $right);
        }

        return $left;
    }

    private function parseExponentiation(): ASTNode
    {
        $left = $this->parseTerm();

        while (!$this->isAtEnd()) {
            $this->skipWhitespace();
            $operator = $this->peekOperator();

            if ($operator !== '**') {
                break;
            }

            $this->consumeOperator($operator);
            $this->skipWhitespace();

            $right = $this->parseTerm();
            $left = new BinaryOpNode($operator, $left, $right);
        }

        return $left;
    }

    private function parseTerm(): ASTNode
    {
        $this->skipWhitespace();

        // Try to parse function call
        if ($node = $this->tryParseFunction()) {
            return $node;
        }

        // Try to parse string literal
        if ($this->peek() === '"' || $this->peek() === "'") {
            return $this->parseStringLiteral();
        }

        // Try to parse number
        if (is_numeric($this->peek()) || $this->peek() === '-') {
            return $this->parseNumber();
        }

        // Try to parse variable
        if (ctype_alpha($this->peek()) || $this->peek() === '_') {
            return $this->parseVariable();
        }

        // Handle parentheses
        if ($this->peek() === '(') {
            $this->consume('(');
            $node = $this->parseExpression();
            $this->skipWhitespace();
            $this->consume(')');

            return $node;
        }

        throw new RuntimeException("Unexpected character '{$this->peek()}' at position {$this->position}");
    }

    private function tryParseFunction(): ?FunctionNode
    {
        $savedPosition = $this->position;

        // Try to match function name
        $functionName = $this->peekFunctionName();
        if ($functionName === null) {
            return null;
        }

        // Check if followed by (
        $pos = $this->position + strlen($functionName);
        if ($pos >= $this->length || $this->input[$pos] !== '(') {
            return null;
        }

        // Consume function name and opening parenthesis
        $this->position = $pos + 1;

        // Parse arguments
        $arguments = [];
        $this->skipWhitespace();

        if ($this->peek() !== ')') {
            do {
                $this->skipWhitespace();
                $arguments[] = $this->parseExpression();
                $this->skipWhitespace();

                if ($this->peek() === ';') {
                    $this->consume(';');
                } elseif ($this->peek() !== ')') {
                    throw new RuntimeException("Expected ';' or ')' at position $this->position");
                }
            } while ($this->peek() !== ')');
        }

        $this->consume(')');

        return new FunctionNode(strtoupper($functionName), $arguments);
    }

    private function parseStringLiteral(): LiteralNode
    {
        $quote = $this->peek();
        $this->consume($quote);

        $value = '';
        while (!$this->isAtEnd() && $this->peek() !== $quote) {
            if ($this->peek() === '\\' && $this->peekNext() === $quote) {
                $this->position++; // Skip backslash
                $value .= $this->peek();
                $this->position++;
            } else {
                $value .= $this->peek();
                $this->position++;
            }
        }

        $this->consume($quote);

        return new LiteralNode($value);
    }

    private function parseNumber(): LiteralNode
    {
        $start = $this->position;

        if ($this->peek() === '-') {
            $this->position++;
        }

        while (!$this->isAtEnd() && (is_numeric($this->peek()) || $this->peek() === '.')) {
            $this->position++;
        }

        $value = substr($this->input, $start, $this->position - $start);

        return new LiteralNode(str_contains($value, '.') ? (float) $value : (int) $value);
    }

    private function parseVariable(): ASTNode
    {
        $start = $this->position;

        while (!$this->isAtEnd() && (ctype_alnum($this->peek()) || $this->peek() === '_' || $this->peek() === '.' || $this->peek() === '*')) {
            $this->position++;
        }

        $path = substr($this->input, $start, $this->position - $start);

        // Transform wildcards to PLUCK calls if present
        if (str_contains($path, '*')) {
            $transformed = WildcardTransformer::transform($path);
            // Reparse the transformed expression
            $savedInput = $this->input;
            $savedPosition = $this->position;
            $savedLength = $this->length;

            $this->input = $transformed;
            $this->length = strlen($transformed);
            $this->position = 0;

            $node = $this->parseExpression();

            // Restore state
            $this->input = $savedInput;
            $this->position = $savedPosition;
            $this->length = $savedLength;

            return $node;
        }

        return new VariableNode($path);
    }

    private function peekFunctionName(): ?string
    {
        // Get registered function names from registry
        $pattern = FormulaRegistry::$FORMULA_FUNCTION_PATTERN;

        if (preg_match($pattern, $this->input, $matches, 0, $this->position)) {
            return rtrim($matches[1], '(');
        }

        return null;
    }

    private const array THREE_CHAR_OPS = ['<=>' => true];
    private const array TWO_CHAR_OPS = ['==' => true, '!=' => true, '<=' => true, '>=' => true, '**' => true, '&&' => true, '||' => true, '??' => true, '?:' => true];
    private const array SINGLE_CHAR_OPS = ['+' => true, '-' => true, '*' => true, '/' => true, '%' => true, '>' => true, '<' => true, '=' => true, '!' => true];

    private function peekOperator(): ?string
    {
        $char = $this->peek();

        // Three-character operators
        if ($this->position + 2 < $this->length) {
            $threeChar = $char . $this->input[$this->position + 1] . $this->input[$this->position + 2];
            if (isset(self::THREE_CHAR_OPS[$threeChar])) {
                return $threeChar;
            }
        }

        // Two-character operators
        if ($this->position + 1 < $this->length) {
            $twoChar = $char . $this->input[$this->position + 1];
            if (isset(self::TWO_CHAR_OPS[$twoChar])) {
                return $twoChar;
            }
        }

        // Single-character operators
        if (isset(self::SINGLE_CHAR_OPS[$char])) {
            return $char;
        }

        return null;
    }

    private function consumeOperator(string $operator): void
    {
        $length = strlen($operator);
        if (substr($this->input, $this->position, $length) !== $operator) {
            throw new RuntimeException("Expected operator '$operator' at position {$this->position}");
        }
        $this->position += $length;
    }

    private function peek(): string
    {
        if ($this->isAtEnd()) {
            return '';
        }

        return $this->input[$this->position];
    }

    private function peekNext(): string
    {
        if ($this->position + 1 >= $this->length) {
            return '';
        }

        return $this->input[$this->position + 1];
    }

    private function consume(string $expected): void
    {
        if ($this->peek() !== $expected) {
            throw new RuntimeException("Expected '$expected' but got '{$this->peek()}' at position {$this->position}");
        }
        $this->position++;
    }

    private function skipWhitespace(): void
    {
        while (!$this->isAtEnd() && ctype_space($this->peek())) {
            $this->position++;
        }
    }

    private function isAtEnd(): bool
    {
        return $this->position >= $this->length;
    }
}
