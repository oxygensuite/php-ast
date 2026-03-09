<?php

namespace OxygenSuite\PhpAst\Formulas;

use Exception;

/**
 * Responsible for parsing and evaluating mathematical expressions
 */
class Math
{
    /**
     * @throws Exception
     */
    public static function eval(string $expression): float
    {
        if (empty($expression)) {
            return 0;
        }

        $expression = preg_replace('/\s+/', '', $expression);

        if (! preg_match('/^[\d+\-*\/.() ]+$/', $expression)) {
            throw new Exception('Invalid expression');
        }

        return self::parseExpression($expression);
    }

    /**
     * @throws Exception
     */
    private static function parseExpression(string $expr): float
    {
        $tokens = self::tokenize($expr, '/([+\-])/');

        if (count($tokens) === 1) {
            return self::parseTerm($tokens[0]);
        }

        $result = self::parseTerm($tokens[0]);
        for ($i = 1, $count = count($tokens); $i < $count; $i += 2) {
            $result += $tokens[$i] === '+'
                ? self::parseTerm($tokens[$i + 1])
                : -self::parseTerm($tokens[$i + 1]);
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    private static function parseTerm(string $term): float
    {
        $tokens = self::tokenize($term, '/([*\/])/');

        if (count($tokens) === 1) {
            return self::parseFactor($tokens[0]);
        }

        $result = self::parseFactor($tokens[0]);
        for ($i = 1, $count = count($tokens); $i < $count; $i += 2) {
            $operand = self::parseFactor($tokens[$i + 1]);

            if ($tokens[$i] === '*') {
                $result *= $operand;
            } else {
                if ($operand == 0) {
                    throw new Exception('Division by zero');
                }
                $result /= $operand;
            }
        }

        return $result;
    }

    private static function tokenize(string $expr, string $operatorPattern): array
    {
        $tokens = [];
        $current = '';
        $depth = 0;
        $length = strlen($expr);

        preg_match('/\[([^]]+)]/', $operatorPattern, $matches);
        $operators = str_replace('\\', '', $matches[1] ?? '');

        for ($i = 0; $i < $length; $i++) {
            $char = $expr[$i];

            if ($char === '(') {
                $depth++;
                $current .= $char;
            } elseif ($char === ')') {
                $depth--;
                $current .= $char;
            } elseif ($depth === 0 && str_contains($operators, $char)) {
                if ($current !== '') {
                    $tokens[] = $current;
                    $current = '';
                }
                $tokens[] = $char;
            } else {
                $current .= $char;
            }
        }

        if ($current !== '') {
            $tokens[] = $current;
        }

        return $tokens;
    }

    /**
     * @throws Exception
     */
    private static function parseFactor(string $factor): float
    {
        if (preg_match('/^\((.*)\)$/', $factor, $matches)) {
            return self::parseExpression($matches[1]);
        }

        return (float) $factor;
    }
}
