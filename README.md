# oxygensuite/php-ast

A lightweight formula engine for PHP built on an Abstract Syntax Tree (AST). Parse, evaluate, and transform expressions with support for variables, functions, wildcards, and operator precedence.

## Requirements

- PHP 8.4+
- ext-ctype
- ext-mbstring

## Installation

```bash
composer require oxygensuite/php-ast
```

## Quick Start

```php
use OxygenSuite\PhpAst\Formulas\FormulaEvaluator;

$evaluator = new FormulaEvaluator();

$data = [
    'lines' => [
        ['quantity' => 2, 'unit_price' => 10.0, 'discount' => 0],
        ['quantity' => 3, 'unit_price' => 15.0, 'discount' => 5],
        ['quantity' => 1, 'unit_price' =>  8.0, 'discount' => 0],
    ],
];

// Variable access (dot-notation)
$evaluator->evaluate('lines.0.quantity', $data); // 2

// Arithmetic
$evaluator->evaluate('lines.0.quantity * lines.0.unit_price', $data); // 20.0

// Wildcards
$evaluator->evaluate('SUM(lines.*.quantity)', $data); // 6

// Functions
$evaluator->evaluate('MAP(lines; quantity * unit_price - discount)', $data); // [20, 40, 8]
```

## Variables

Variables use **dot-notation** for nested property access. Types are preserved (string, int, float, bool, null, array).

| Syntax             | Resolves to                     |
|--------------------|---------------------------------|
| `uid`              | `$data['uid']`                  |
| `lines.0.quantity` | `$data['lines'][0]['quantity']` |
| `a.b.c`            | `$data['a']['b']['c']`          |

Variables can be used directly in expressions:

```php
$evaluator->evaluate('lines.0.quantity * lines.0.unit_price', $data);
$evaluator->evaluate('(base_price + tax) * quantity', $data);
```

## Wildcards

The `*` wildcard extracts values across all items in an array.

```php
// Single-level: lines.*.quantity → PLUCK(lines; "quantity")
$evaluator->evaluate('lines.*.quantity', $data); // [2, 3, 1]

// Multi-level: lines.*.taxes.*.amount → extracts all tax amounts from all lines
$evaluator->evaluate('SUM(lines.*.taxes.*.amount)', $data);
```

## Operators

Listed from highest to lowest precedence:

| Precedence | Operators                         | Description                      |
|------------|-----------------------------------|----------------------------------|
| 1          | `**`                              | Exponentiation                   |
| 2          | `*` `/` `%`                       | Multiplication, division, modulo |
| 3          | `+` `-`                           | Addition, subtraction            |
| 4          | `==` `!=` `<` `>` `<=` `>=` `<=>` | Comparison                       |
| 5          | `&&`                              | Logical AND                      |
| 6          | `\|\|`                            | Logical OR                       |
| 7          | `??` `?:`                         | Null coalescing, elvis           |

Parentheses override precedence: `(a + b) * c`.

## Functions

All functions use **semicolons** (`;`) as argument separators.

### Aggregation

| Function        | Description           | Example                   |
|-----------------|-----------------------|---------------------------|
| `SUM(values)`   | Sum of numeric values | `SUM(lines.*.quantity)`   |
| `AVG(values)`   | Average               | `AVG(lines.*.unit_price)` |
| `MIN(values)`   | Minimum value         | `MIN(lines.*.unit_price)` |
| `MAX(values)`   | Maximum value         | `MAX(lines.*.quantity)`   |
| `COUNT(values)` | Number of elements    | `COUNT(lines.*.quantity)` |

### Rounding

| Function                  | Description                 | Example                           |
|---------------------------|-----------------------------|-----------------------------------|
| `ROUND(value; precision)` | Round to precision decimals | `ROUND(10.567; 2)` &rarr; `10.57` |
| `CEIL(value)`             | Round up                    | `CEIL(10.1)` &rarr; `11`          |
| `FLOOR(value)`            | Round down                  | `FLOOR(10.9)` &rarr; `10`         |

### Array Manipulation

| Function                   | Description                              | Example                             |
|----------------------------|------------------------------------------|-------------------------------------|
| `PLUCK(array; "key")`      | Extract column from array of objects     | `PLUCK(lines; "quantity")`          |
| `FLAT(array; depth)`       | Flatten nested arrays (default depth: 1) | `FLAT([[1,2],[3,[4,5]]]; 2)`        |
| `MAP(array; expr)`         | Apply expression per item                | `MAP(lines; quantity * unit_price)` |
| `FILTER(array; condition)` | Filter items by condition                | `FILTER(lines; unit_price > 10)`    |
| `GROUP(array; "key")`      | Group items by key                       | `GROUP(lines; "tax_category")`      |
| `SORT(array)`              | Sort array                               | `SORT(lines.*.quantity)`            |
| `JOIN(array; separator)`   | Join into string                         | `JOIN(lines.*.quantity; ",")`       |
| `FIRST(array)`             | First element                            | `FIRST(lines)`                      |
| `LAST(array)`              | Last element                             | `LAST(lines)`                       |

### Conditional

| Function                        | Description      | Example                               |
|---------------------------------|------------------|---------------------------------------|
| `IF(cond; true_val; false_val)` | Conditional      | `IF(quantity > 10; "bulk"; "single")` |
| `VALUE(array; "key")`           | Get value by key | `VALUE(line; "quantity")`             |

## Composing Functions

Functions can be nested and combined with arithmetic:

```php
// Sum of (quantity * unit_price) per line
$evaluator->evaluate('SUM(MAP(lines; quantity * unit_price))', $data);

// Filter then sum
$evaluator->evaluate('SUM(PLUCK(FILTER(lines; tax_category == 1); "net_amount"))', $data);

// Grouped aggregation
$evaluator->evaluate('GROUP(lines; "tax_category")', $data);
// → [1 => [...], 2 => [...], 3 => [...]]

// Arithmetic on aggregated results
$evaluator->evaluate('SUM(lines.*.net_amount) + SUM(lines.*.vat_amount)', $data);
```

## Context Providers

For injecting external values (e.g., configuration, session data) into formula evaluation:

```php
use OxygenSuite\PhpAst\ContextProvider\ContextProvider;

class MyContext implements ContextProvider
{
    public function getContextValue(string $key): mixed
    {
        return match ($key) {
            'tax_rate' => 0.24,
            'currency' => 'EUR',
            default => null,
        };
    }
}

$evaluator = new FormulaEvaluator(new MyContext());
$evaluator->evaluate('unit_price * tax_rate', $data); // uses tax_rate from context
```

Context values take priority over data array values.

## Custom Functions

### Simple Function

Implement the `Formula` interface:

```php
use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\Formula;

readonly class DoubleFunction implements Formula
{
    public function execute(array $arguments, array $data, ASTEvaluator $evaluator): float|int|string|array
    {
        return ($arguments[0] ?? 0) * 2;
    }
}
```

### AST-Aware Function

For functions that need per-item evaluation (like MAP/FILTER), implement `ASTFormula` instead. It receives raw AST nodes so you can evaluate expressions against each item:

```php
use OxygenSuite\PhpAst\AST\ASTEvaluator;
use OxygenSuite\PhpAst\Formulas\ASTFormula;

readonly class MyTransform implements ASTFormula
{
    public function execute(array $astNodes, array $data, ASTEvaluator $evaluator): mixed
    {
        [$itemsNode, $callbackNode] = $astNodes;
        $items = $itemsNode->accept($evaluator, $data);

        return array_map(
            fn($item) => $callbackNode->accept($evaluator, $item),
            $items,
        );
    }
}
```

## Caching

Parsed ASTs are automatically cached per evaluator instance (default: 1000 entries). Repeated evaluation of the same formula string skips parsing entirely.

```php
$evaluator->getCacheStats(); // ['hits' => 42, 'misses' => 10, 'size' => 10, 'hitRate' => 0.807]
$evaluator->clearCache();
```

## Error Handling

| Scenario             | Behavior                  |
|----------------------|---------------------------|
| Missing variable     | Returns `null`            |
| Division by zero     | Returns `0`               |
| Unknown function     | Throws `RuntimeException` |
| Invalid expression   | Throws `RuntimeException` |
| Recursion depth > 32 | Throws `RuntimeException` |

## License

MIT
