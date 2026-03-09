<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\AbstractFormula;

readonly class AvgFunction extends AbstractFormula
{
    public function executeWithArgs(array $arguments, array $data, ASTEvaluator $evaluator): float
    {
        if (empty($arguments)) {
            return 0;
        }

        // If a single argument that's an array, calculate average
        if (count($arguments) === 1 && is_array($arguments[0])) {
            $values = $arguments[0];
            $sum = array_sum(array_map('floatval', $values));
            $count = count($values);

            return $count === 0 ? 0 : ($sum / $count);
        }

        // If multiple arguments, calculate the average of all
        $sum = 0;
        $count = 0;
        foreach ($arguments as $arg) {
            if (is_array($arg)) {
                $sum += array_sum(array_map('floatval', $arg));
                $count += count($arg);
            } elseif (is_numeric($arg)) {
                $sum += $arg;
                $count++;
            }
        }

        return $count === 0 ? 0 : ($sum / $count);
    }
}
