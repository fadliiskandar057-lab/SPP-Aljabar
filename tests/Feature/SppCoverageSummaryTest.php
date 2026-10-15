<?php

namespace Tests\Feature;

use App\Models\Tagihan;
use App\Services\SppCoverageSummary;
use PHPUnit\Framework\TestCase;

class SppCoverageSummaryTest extends TestCase
{
    private function summary(array $periods): array
    {
        return (new SppCoverageSummary)->summarize(collect($periods)->map(
            fn ($period) => new Tagihan(['bulan' => $period[0], 'tahun' => $period[1], 'status' => $period[2]])
        ));
    }

    public function test_unpaid_month_stops_coverage_even_when_later_month_is_paid(): void
    {
        $result = $this->summary([
            ['Maret', 2026, 'lunas'], ['Januari', 2026, 'lunas'], ['Februari', 2026, 'menunggu_konfirmasi'],
        ]);
        $this->assertSame('Januari 2026', $result['through']);
        $this->assertSame('Maret 2026', $result['latest']);
        $this->assertSame('Februari 2026', $result['first_unpaid']);
    }

    public function test_free_month_and_year_boundary_are_continuous(): void
    {
        $result = $this->summary([['Januari', 2027, 'gratis'], ['Desember', 2026, 'lunas']]);
        $this->assertSame('Januari 2027', $result['through']);
        $this->assertNull($result['first_unpaid']);
    }

    public function test_missing_month_stops_coverage(): void
    {
        $result = $this->summary([['Januari', 2026, 'lunas'], ['Maret', 2026, 'lunas']]);
        $this->assertSame('Januari 2026', $result['through']);
        $this->assertSame('Maret 2026', $result['latest']);
    }

    public function test_empty_data_does_not_claim_coverage(): void
    {
        $this->assertSame(['through' => null, 'latest' => null, 'first_unpaid' => null], $this->summary([]));
    }
}
