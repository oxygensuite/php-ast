<?php

namespace OxygenSuite\PhpAst\Formulas;

use ReflectionClass;

/**
 * Registry for formula function handlers
 * Open/Closed Principle: Extensible without modification
 */
class FormulaRegistry
{
    public static string $FORMULA_PATTERN = '';
    public static string $FORMULA_FUNCTION_PATTERN = '';

    /** @var array<string, Formula|ASTFormula> */
    private array $handlers = [];

    private static FormulaRegistry $registry;

    private function __construct()
    {
        $this->registerFunctions();
        $this->updatePatterns();
    }

    /**
     * Ensure the registry is initialized
     */
    public static function ensureInitialized(): void
    {
        self::$registry ??= new self();
    }

    /**
     * @param string $formula
     *
     * @return array{0: Formula|ASTFormula|null, 1: string|null}
     */
    public static function getHandler(string $formula): array
    {
        self::ensureInitialized();

        // First, check if it starts with a function name followed by (
        $escapedNames = array_map(
            fn(string $name) => preg_quote($name, '/'),
            array_keys(self::$registry->handlers),
        );
        if (! preg_match('/^(' . implode('|', $escapedNames) . ')\(/', $formula, $matches)) {
            return [null, null];
        }

        $functionName = $matches[1];

        // Now extract the arguments by properly matching balanced parentheses
        $args = self::extractBalancedParentheses($formula, strlen($functionName) + 1);

        if ($args === null) {
            return [null, null];
        }

        // Verify the entire formula is consumed (function + args + closing paren)
        if (strlen($functionName) + 1 + strlen($args) + 1 !== strlen($formula)) {
            return [null, null];
        }

        return [self::$registry->handlers[$functionName] ?? null, $args];
    }

    /**
     * Get handler by function name (for AST-based evaluation)
     */
    public static function getHandlerByName(string $name): Formula|ASTFormula|null
    {
        self::ensureInitialized();

        return self::$registry->handlers[strtoupper($name)] ?? null;
    }

    /**
     * Extract content within balanced parentheses starting from a given position
     * Returns the content without the outer parentheses, or null if unbalanced
     */
    private static function extractBalancedParentheses(string $text, int $startPos): ?string
    {
        $depth = 1; // We're already past the opening paren
        $length = strlen($text);
        $content = '';

        for ($i = $startPos; $i < $length; $i++) {
            $char = $text[$i];

            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
                if ($depth === 0) {
                    // Found the matching closing paren
                    return $content;
                }
            }
            $content .= $char;
        }

        // Unbalanced parentheses
        return null;
    }

    private function registerFunctions(): void
    {
        // Get all functions from the ./Functions directory and register them
        $functionFiles = glob(__DIR__ . '/Functions/*.php');
        foreach ($functionFiles as $file) {
            $className = pathinfo($file, PATHINFO_FILENAME);
            $fullClassName = __NAMESPACE__ . '\\Functions\\' . $className;
            if (class_exists($fullClassName) && (is_subclass_of($fullClassName, Formula::class) || is_subclass_of($fullClassName, ASTFormula::class))) {
                $reflection = new ReflectionClass($fullClassName);
                if (! $reflection->isInstantiable() || $reflection->getFileName() !== realpath($file)) {
                    continue;
                }
                // Get the first word from the class name as the function name (e.g., SumFunction => SUM)
                $functionName = substr($className, 0, -8);
                $this->register(strtoupper($functionName), new $fullClassName());
            }
        }
    }

    private function register(string $name, Formula|ASTFormula $handler): void
    {
        $upperName = strtoupper($name);

        // Only allow alphanumeric function names to prevent regex injection
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $upperName)) {
            return;
        }

        $this->handlers[$upperName] = $handler;
    }

    private function updatePatterns(): void
    {
        // Update regex patterns with registered function names
        // Escape function names to prevent ReDoS via regex metacharacters
        $escapedNames = array_map(
            fn(string $name) => preg_quote($name, '/'),
            array_keys($this->handlers),
        );
        $functionNames = implode('|', $escapedNames);

        self::$FORMULA_PATTERN = '/^(' . $functionNames . ')\((.*)\)$/s';
        self::$FORMULA_FUNCTION_PATTERN = '/(' . $functionNames . ')\(/';
    }
}
