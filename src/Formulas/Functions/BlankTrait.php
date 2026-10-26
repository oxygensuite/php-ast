<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

trait BlankTrait
{
    /**
     * Whether a value is not there: missing, text of only whitespace, or a list or record without items. Unlike
     * truthiness, zero, "0" and false are values.
     */
    private function isBlank(mixed $value): bool
    {
        if (is_array($value)) {
            return $value === [];
        }

        return $value === null || (is_string($value) && trim($value) === '');
    }
}
