<?php

namespace OxygenSuite\PhpAst\ContextProvider;

/**
 * Transforms wildcard paths into nested PLUCK function calls
 * Example: lines.*.quantity -> PLUCK(lines; quantity)
 * Example: lines.*.taxes.*.amount -> PLUCK(PLUCK(lines; taxes); amount)
 */
class WildcardTransformer
{
    /**
     * Check if a path contains wildcards
     */
    public static function hasWildcards(string $path): bool
    {
        return str_contains($path, '*');
    }

    /**
     * Transform a wildcard path into nested PLUCK calls
     *
     * Examples:
     * - lines.*.quantity -> PLUCK(lines; "quantity")
     * - lines.*.taxes.*.amount -> PLUCK(PLUCK(lines; "taxes"); "amount")
     * - items.*.details.*.props.*.value -> PLUCK(PLUCK(PLUCK(items; "details"); "props"); "value")
     */
    public static function transform(string $path): string
    {
        if (!self::hasWildcards($path)) {
            return $path;
        }

        // Split the path into segments
        $segments = explode('.', $path);

        // Find wildcard positions and build a nested PLUCK structure
        $wildcardIndices = [];
        foreach ($segments as $index => $segment) {
            if ($segment === '*') {
                $wildcardIndices[] = $index;
            }
        }

        if (empty($wildcardIndices)) {
            return $path;
        }

        // Build nested PLUCK calls from the inside out
        return self::buildNestedPluck($segments, $wildcardIndices);
    }

    /**
     * Build nested PLUCK calls based on wildcard positions
     *
     * Examples:
     * lines.*.quantity -> PLUCK(lines; "quantity")
     * lines.*.taxes.*.amount -> PLUCK(FLAT(PLUCK(lines; "taxes")); "amount")
     * items.*.details.*.props.*.value -> PLUCK(FLAT(PLUCK(FLAT(PLUCK(items; "details")); "props")); "value")
     */
    private static function buildNestedPluck(array $segments, array $wildcardIndices): string
    {
        // Start with the innermost source (before the first wildcard)
        $sourceSegments = array_slice($segments, 0, $wildcardIndices[0]);
        $source = implode('.', $sourceSegments);

        // Process each wildcard from left to right
        foreach ($wildcardIndices as $idx => $wildcardPos) {
            // Get the column name (segments after this wildcard until the next wildcard or end)
            $columnStart = $wildcardPos + 1;

            if ($idx < count($wildcardIndices) - 1) {
                // Not the last wildcard: take segments until the next wildcard
                $columnEnd = $wildcardIndices[$idx + 1];
                $columnSegments = array_slice($segments, $columnStart, $columnEnd - $columnStart);
            } else {
                // Last wildcard: take all remaining segments
                $columnSegments = array_slice($segments, $columnStart);
            }

            $column = implode('.', $columnSegments);

            // Build PLUCK expression with a quoted column name
            if (!empty($column)) {
                $source = "PLUCK($source; \"$column\")";

                // If this is not the last wildcard, we need to flatten the result
                // because PLUCK will return an array of arrays
                if ($idx < count($wildcardIndices) - 1) {
                    $source = "FLAT($source)";
                }
            } else {
                $source = "PLUCK($source)";
            }
        }

        return $source;
    }
}
