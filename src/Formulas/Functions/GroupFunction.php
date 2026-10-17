<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;
use OxygenSuite\PhpAst\Formulas\FunctionDefinition;

#[FunctionDefinition(
    signature: 'GROUP(array; "field")',
    description: 'Groups the items by a field (field name quoted): each value of the field maps to the list of its items.',
    example: 'GROUP(lines; "tax_category")',
    minArguments: 2,
    maxArguments: 2,
    listArgument: 0,
)]
readonly class GroupFunction implements Formula
{
    /**
     * Groups an array by a specified key.
     *
     * Example: GROUP(lines; "tax_category")
     * Example: GROUP([[id: 1, category: 'A'], [id: 2, category: 'B'], [id: 3, category: 'A']]; "category")
     */
    public function execute(array $arguments, array $data, ASTEvaluator $evaluator): array
    {
        if (count($arguments) < 2) {
            return [];
        }

        [$source, $key] = $arguments;

        // Handle the result - if it's not an array, make it one
        $array = is_array($source) ? $source : [$source];

        return $this->groupBy($array, (string) $key);
    }

    /**
     * Groups an array by the specified key.
     *
     * @param array $array The array to group
     * @param string $key The key to group by
     *
     * @return array The grouped array
     */
    private function groupBy(array $array, string $key): array
    {
        $result = [];

        foreach ($array as $item) {
            if (is_array($item) && isset($item[$key])) {
                $groupValue = $item[$key];

                if (!isset($result[$groupValue])) {
                    $result[$groupValue] = [];
                }

                $result[$groupValue][] = $item;
            }
        }

        return $result;
    }
}
