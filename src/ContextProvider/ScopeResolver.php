<?php

namespace OxygenSuite\PhpAst\ContextProvider;

/**
 * Implemented by a ContextProvider that reads every name of a formula itself, from the scope it belongs to: an item of
 * MAP() or FILTER(), a scope of its own such as the item of a loop, or its data. A scope that has the name hides the
 * same name outside it, also where its value is missing.
 */
interface ScopeResolver
{
    /**
     * @param string $path The name as the formula writes it, such as "taxes" or "extra_fields.size"
     * @param list<array{item: array, path: ?string}> $items The items of MAP() and FILTER() the name is read in,
     *                                                       innermost first, each with the path of its list as the
     *                                                       formula writes it, such as "taxes", when it is a path
     *
     * @return mixed The name's value in the scope it belongs to
     */
    public function resolveName(string $path, array $items): mixed;
}
