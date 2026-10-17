<?php

namespace Tests\Unit;

use RuntimeException;
use Tests\FormulaTestCase;

class ConcatFormulaTest extends FormulaTestCase
{
    public function test_concat_joins_text_and_numbers()
    {
        $result = $this->evaluate('CONCAT(header.series; "-"; header.invoice_number; " / "; summary.total_net_amount)');

        $this->assertSame('ΑΠΥ-0042 / 173.5', $result);
    }

    public function test_concat_reads_a_missing_value_as_nothing()
    {
        $result = $this->evaluate('CONCAT("A"; header.missing; "B")');

        $this->assertSame('AB', $result);
    }

    public function test_concat_joins_the_items_of_a_list()
    {
        $result = $this->evaluate('CONCAT("Codes: "; lines.*.item_code)');

        $this->assertSame('Codes: P-001P-002P-003P-004P-005', $result);
    }

    public function test_concat_combines_with_conditions()
    {
        $result = $this->evaluate('IF(header.series && header.invoice_number; CONCAT(header.series; " "; header.invoice_number); "")');

        $this->assertSame('ΑΠΥ 0042', $result);
    }

    public function test_concat_rejects_a_list_of_records()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CONCAT() joins text and numbers; argument 2 holds a list or a record');

        $this->evaluate('CONCAT("Lines: "; lines)');
    }
}
