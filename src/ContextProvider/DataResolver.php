<?php

namespace OxygenSuite\PhpAst\ContextProvider;

/**
 * Responsible for resolving data paths and variables
 */
readonly class DataResolver
{
    public function __construct(private ?ContextProvider $contextProvider = null) {}

    /**
     * Resolve a dotted path in an array (e.g., "customer.name")
     *
     * @param array $data The data array to resolve from
     * @param string $key The dotted path key
     * @param mixed $default The default value if the path not found
     *
     * @return mixed The resolved value or default
     */
    public function resolve(array $data, string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        if (empty($parts)) {
            return $default;
        }

        $current = $data;

        foreach ($parts as $part) {
            $current = $current[$part] ?? $default;

            if ($current === null) {
                return $default;
            }
        }

        return $current;
    }

    /**
     * Resolve a variable path to its value (for AST evaluation)
     *
     * @param string $path Variable path (e.g., "invoice.total", "line.quantity")
     * @param array $data Data context
     *
     * @return mixed Resolved value
     */
    public function resolveVariable(string $path, array $data): mixed
    {
        // Check context first (from iteration)
        if ($this->contextProvider !== null) {
            $contextValue = $this->contextProvider->getContextValue($path);
            if ($contextValue !== null) {
                return $contextValue;
            }
        }

        // Fallback to resolving from data
        return $this->resolve($data, $path);
    }

}
