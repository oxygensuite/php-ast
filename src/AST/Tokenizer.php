<?php

namespace OxygenSuite\PhpAst\AST;

use OxygenSuite\PhpAst\ContextProvider\WildcardTransformer;
use OxygenSuite\PhpAst\Formulas\FormulaRegistry;

/**
 * Tokenizes formula strings into AST
 */
class Tokenizer
{
    private const int MAX_FORMULA_LENGTH = 10_000;

    private int $position = 0;

    /** How many arguments evaluated per item, such as FILTER's condition, the current position is inside */
    private int $itemArguments = 0;
    private string $input = '';
    private int $length = 0;

    public function tokenize(string $formula): ASTNode
    {
        if (strlen($formula) > self::MAX_FORMULA_LENGTH) {
            throw new ParseException('Formula exceeds maximum allowed length');
        }

        FormulaRegistry::ensureInitialized();

        $this->input = trim($formula);
        $this->length = strlen($this->input);
        $this->position = 0;
        $this->itemArguments = 0;

        $node = $this->parseExpression();

        // Whatever the expression could not read is a mistake, not something to drop
        $this->skipWhitespace();
        if (!$this->isAtEnd()) {
            throw $this->unexpected();
        }

        return $node;
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

        // A leading minus negates the term after it: a number, a variable, a call or a bracket
        if ($this->peek() === '-') {
            $this->position++;
            $operand = $this->parseTerm();

            if ($operand instanceof LiteralNode && (is_int($operand->value) || is_float($operand->value))) {
                return new LiteralNode(-$operand->value);
            }

            return new BinaryOpNode('-', new LiteralNode(0), $operand);
        }

        // Try to parse number
        if (is_numeric($this->peek())) {
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

        throw $this->unexpected();
    }

    /**
     * A call starts with a word directly followed by "(". Names are case-sensitive: only upper-case registered ones exist.
     *
     * @throws ParseException When the word is not a function, or the call is malformed or has the wrong argument count
     */
    private function tryParseFunction(): ?FunctionNode
    {
        if (!preg_match('/\G([A-Za-z_]\w*)\(/', $this->input, $matches, 0, $this->position)) {
            return null;
        }

        $functionName = $matches[1];
        $definition = FormulaRegistry::definitions()[$functionName]
            ?? throw new ParseException("Unknown function '$functionName' at position {$this->position}");

        // Consume function name and opening parenthesis
        $this->position += strlen($functionName) + 1;

        // Parse arguments
        $arguments = [];
        $this->skipWhitespace();

        if ($this->peek() !== ')') {
            while (true) {
                $this->skipWhitespace();

                if ($this->peek() === ';' || $this->peek() === ')') {
                    throw new ParseException("Missing argument of {$functionName}() at position {$this->position}");
                }

                $ofItem = count($arguments) === $definition->itemArgument;
                $this->itemArguments += (int) $ofItem;
                $arguments[] = $this->parseExpression();
                $this->itemArguments -= (int) $ofItem;
                $this->skipWhitespace();

                if ($this->peek() === ')') {
                    break;
                }

                if ($this->peek() !== ';') {
                    throw $this->unexpected("';' or ')'");
                }

                $this->consume(';');
            }
        }

        $this->consume(')');

        // Checked here, where every call is built, so a call is rejected even where the data never reaches it
        if (!$definition->acceptsArgumentCount(count($arguments))) {
            throw new ParseException(sprintf('%s() expects %s argument(s), %d given', $functionName, $definition->expectedArguments(), count($arguments)));
        }

        // A wildcard path the function reads as its list, as SUM(lines.*.net_amount) does, is marked for its resolver
        foreach ($arguments as $index => $argument) {
            if ($argument instanceof WildcardPathNode && $definition->readsAsList($index, count($arguments))) {
                $arguments[$index] = $argument->readAsList();
            }
        }

        return new FunctionNode($functionName, $arguments);
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

        while (!$this->isAtEnd() && (is_numeric($this->peek()) || $this->peek() === '.')) {
            $this->position++;
        }

        $value = substr($this->input, $start, $this->position - $start);

        return new LiteralNode(str_contains($value, '.') ? (float) $value : (int) $value);
    }

    private function parseVariable(): ASTNode
    {
        $start = $this->position;

        while (!$this->isAtEnd() && $this->isPathCharacter()) {
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

            return new WildcardPathNode($path, $node, ofItem: $this->itemArguments > 0);
        }

        return new VariableNode($path);
    }

    /**
     * A "*" is a wildcard only right after a dot, as in lines.*.quantity; elsewhere it multiplies, as in qty*price
     */
    private function isPathCharacter(): bool
    {
        $char = $this->peek();

        return ctype_alnum($char) || $char === '_' || $char === '.' || ($char === '*' && $this->input[$this->position - 1] === '.');
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
            throw new ParseException("Expected operator '$operator' at position {$this->position}");
        }
        $this->position += $length;
    }

    /**
     * The error for what stands at the current position, a character or the formula's end, where the grammar expected
     * something else, such as "')'"
     */
    private function unexpected(?string $expected = null): ParseException
    {
        $found = $this->isAtEnd() ? 'end of the formula' : "character '{$this->peek()}'";

        return new ParseException($expected === null
            ? "Unexpected {$found} at position {$this->position}"
            : "Expected {$expected} but found {$found} at position {$this->position}");
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
            throw $this->unexpected("'{$expected}'");
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
