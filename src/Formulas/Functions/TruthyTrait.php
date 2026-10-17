<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

trait TruthyTrait
{
    /**
     * A number is true unless it is zero; a string unless it is "", "0" or "false", so an identifier such as "0000"
     * or "00000001" is true.
     */
    private function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return $value != 0;
        }

        if (is_string($value)) {
            return $value !== '' && strtolower($value) !== 'false' && $value !== '0';
        }

        if (is_array($value)) {
            return count($value) > 0;
        }

        return !empty($value);
    }
}
