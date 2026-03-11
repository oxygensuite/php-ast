<?php

namespace Tests;

use OxygenSuite\PhpAst\Formulas\FormulaEvaluator;

class FormulaTestCase extends TestCase
{
    private array $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->data = require __DIR__ . '/data/invoice.php';
    }

    protected function evaluate(string $formula, ?array $context = null): mixed
    {
        $evaluator = new FormulaEvaluator();

        return $evaluator->evaluate($formula, array_merge($this->data, $context ?? $this->data));
    }
}
