<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\AbstractFormula;

readonly class FlatFunction extends AbstractFormula
{
    /**
     * Flattens an array to a specified depth.
     *
     * Function syntax: FLAT(data/formula; depth)
     * Example: FLAT(nested_array; 1)
     * Example: FLAT([[1, 2], [3, [4, 5]]]; 2)
     */
    public function executeWithArgs(array $arguments, array $data, ASTEvaluator $evaluator): array
    {
        if (empty($arguments)) {
            return [];
        }

        $source = $arguments[0];
        $depth = isset($arguments[1]) ? (int) $arguments[1] : 1;

        // Handle the result - if it's already an array, use it; otherwise make it an array
        $array = is_array($source) ? $source : [$source];

        return $this->flatten($array, $depth);
    }

    /**
     * Recursively flattens an array to the specified depth.
     *
     * @param array $array The array to flatten
     * @param int $depth The depth to flatten (0 = no flattening, 1 = one level, etc.)
     * @return array The flattened array
     */
    private function flatten(array $array, int $depth): array
    {
        $result = [];

        foreach ($array as $item) {
            if (is_array($item) && $depth > 0) {
                // Flatten this item and recurse with depth - 1
                array_push($result, ...$this->flatten($item, $depth - 1));
            } else {
                $result[] = $item;
            }
        }

        return $result;
    }
}
