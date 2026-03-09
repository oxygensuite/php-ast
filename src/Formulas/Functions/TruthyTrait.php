<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

trait TruthyTrait
{
    private function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
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
