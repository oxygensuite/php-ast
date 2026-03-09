<?php

namespace OxygenSuite\PhpAst\ContextProvider;

/**
 * Responsible for resolving data paths and variables
 */
readonly class DataResolver
{
    public function __construct(private ?ContextProvider $contextProvider = null)
    {
    }

    /**
     * Resolve a dotted path in an array (e.g., "customer.name")
     *
     * @param array $data The data array to resolve from
     * @param string $key The dotted path key
     * @param mixed $default The default value if the path not found
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
     * @param string $path Variable path (e.g., "invoice.total", "line.quantity")
     * @param array $data Data context
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

    /**
     * Replace variables in expressions with their values from data context
     */
    public function replaceVariables(string $expression, array $data): mixed
    {
        $trimmed = trim($expression);

        // Check if the entire expression is a single variable path
        // Support paths with letters, underscores, digits, and wildcards (wildcards must be standalone)
        if (preg_match('/^[a-z_][a-z_0-9]*(?:\.[a-z_0-9]+|\.\*)*$/i', $trimmed)) {
            // Check context first (from iteration) - supports simple keys and properties
            if ($this->contextProvider !== null) {
                $contextValue = $this->contextProvider->getContextValue($trimmed);
                if ($contextValue !== null) {
                    return $contextValue;
                }
            }

            // Fallback to resolving from data
            return $this->resolve($data, $trimmed);
        }

        // For complex expressions with operators, replace each variable
        // Match variable paths where * is only allowed as a standalone segment between dots
        return preg_replace_callback('/[a-z_][a-z_0-9]*(?:\.[a-z_0-9]+|\.\*)*/i', function ($matches) use ($data) {
            // Check context first (from iteration) - supports simple keys and properties
            if ($this->contextProvider !== null) {
                $contextValue = $this->contextProvider->getContextValue($matches[0]);
                if ($contextValue !== null && ! is_array($contextValue)) {
                    return $contextValue;
                }
            }

            // Resolve the value from data
            $value = $this->resolve($data, $matches[0]);

            // Return value only if it's scalar (arrays can't be inline in expressions)
            if ($value !== null && ! is_array($value)) {
                return $value;
            }

            return $matches[0];
        }, $expression);
    }
}
