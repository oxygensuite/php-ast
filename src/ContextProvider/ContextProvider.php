<?php

namespace OxygenSuite\PhpAst\ContextProvider;

/**
 * Interface for providing iteration context values to formula evaluators
 * Follows Interface Segregation Principle
 */
interface ContextProvider
{
    /**
     * Retrieve a value from the current iteration context
     *
     * @param string $key The key to retrieve (without $ prefix)
     * @return mixed The context value or null if not found
     */
    public function getContextValue(string $key): mixed;
}
