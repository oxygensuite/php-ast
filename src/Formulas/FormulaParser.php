<?php

namespace OxygenSuite\PhpAst\Formulas;

/**
 * Utility class for parsing formula syntax
 */
class FormulaParser
{
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

}
