<?php

namespace OxygenSuite\PhpAst\Formulas\Functions;

use RuntimeException;

trait TextValuesTrait
{
    /**
     * The values as text, a list item after item, a missing value as ""
     *
     * @param list<mixed> $values
     * @param int $firstPosition The position in the call of the first of them, from 1, for the error
     *
     * @return list<string>
     * @throws RuntimeException When a value holds a list or a record, which has no text of its own
     */
    private function textValues(array $values, string $function, int $firstPosition = 1): array
    {
        $texts = [];

        foreach ($values as $index => $value) {
            foreach (is_array($value) ? $value : [$value] as $item) {
                $texts[] = $this->text($item, $function, $firstPosition + $index);
            }
        }

        return $texts;
    }

    /**
     * @param int $position The argument's position in the call, from 1, for the error
     */
    private function text(mixed $value, string $function, int $position): string
    {
        if (is_array($value) || is_object($value)) {
            throw new RuntimeException(sprintf('%s() joins text and numbers; argument %d holds a list or a record', $function, $position));
        }

        return (string) $value;
    }
}
