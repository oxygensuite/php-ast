<?php

namespace OxygenSuite\PhpAst\Formulas;

/**
 * Utility class for parsing formula syntax
 */
class FormulaParser
{
    /**
     * Extract the next complete formula from text
     */
    public static function extractNextFormula(string $text): ?string
    {
        FormulaRegistry::ensureInitialized();

        if (! preg_match(FormulaRegistry::$FORMULA_FUNCTION_PATTERN, $text, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $start = $matches[0][1];
        $depth = 0;
        $inFormula = false;
        $length = strlen($text);

        for ($i = $start; $i < $length; $i++) {
            $char = $text[$i];

            if ($char === '(') {
                $depth++;
                $inFormula = true;
            } elseif ($char === ')') {
                $depth--;
                if ($depth === 0 && $inFormula) {
                    return substr($text, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }

    /**
     * Remove quotes from string literal
     */
    public static function unquoteString(string $value): string
    {
        if (preg_match('/^["\'](.+)["\']$/', $value, $matches)) {
            return $matches[1];
        }

        return $value;
    }

    public static function splitArguments(string $arguments): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $inQuotes = false;
        $quoteChar = null;
        $length = strlen($arguments);

        for ($i = 0; $i < $length; $i++) {
            $char = $arguments[$i];

            if (($char === '"' || $char === "'") && ($i === 0 || $arguments[$i - 1] !== '\\')) {
                if (!$inQuotes) {
                    $inQuotes = true;
                    $quoteChar = $char;
                } elseif ($char === $quoteChar) {
                    $inQuotes = false;
                }
                $current .= $char;
            } elseif ($char === '(' && !$inQuotes) {
                $depth++;
                $current .= $char;
            } elseif ($char === ')' && !$inQuotes) {
                $depth--;
                $current .= $char;
            } elseif ($char === ';' && $depth === 0 && !$inQuotes) {
                $parts[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if ($current !== '') {
            $parts[] = trim($current);
        }

        return $parts;
    }

    /**
     * Replace all nested formulas in an expression
     */
    public static function replaceNestedFormulas(string $expression, array $data, FormulaEvaluator $evaluator): string
    {
        // Ensure the registry is initialized before accessing patterns
        FormulaRegistry::ensureInitialized();

        $maxIterations = 10;
        $iteration = 0;

        while ($iteration < $maxIterations) {
            $formula = self::extractNextFormula($expression);
            if ($formula === null) {
                break;
            }

            $formulaResult = $evaluator->evaluate($formula, $data);
            // Cast result to string for str_replace (PHP 8.4+ strictness)
            $expression = str_replace($formula, (string) $formulaResult, $expression);
            $iteration++;
        }

        return $expression;
    }
}
