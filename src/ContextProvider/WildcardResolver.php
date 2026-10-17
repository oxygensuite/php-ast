<?php

namespace OxygenSuite\PhpAst\ContextProvider;

/**
 * Implemented by a ContextProvider that decides which items a wildcard path of a formula reads, such as the current
 * line of a loop over lines for "lines.*.quantity"
 */
interface WildcardResolver
{
    /**
     * The path to read instead of a wildcard path as written, such as "lines.4.quantity" for "lines.*.quantity", or
     * null to read it as written, every item
     *
     * @param string $path The path as the formula writes it, such as "lines.*.taxes.*.amount"
     * @param bool $asList Whether a function reads it as its list, as SUM(lines.*.net_amount) does
     */
    public function resolveWildcardPath(string $path, bool $asList): ?string;
}
