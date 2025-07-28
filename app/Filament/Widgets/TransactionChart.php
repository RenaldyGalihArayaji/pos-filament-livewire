<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Carbon;
use Flowframe\Trend\Trend;
use App\Models\Transaction;
use Flowframe\Trend\TrendValue;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class TransactionChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Grafik Total Transaksi';
    protected static string $color = 'danger';

    protected function getData(): array
    {
       $startDate = Carbon::parse($this->filters['start_date'] ?? null);
        $endDate = Carbon::parse($this->filters['end_date'] ?? null);
        $branchId = $this->filters['branch'] ?? null;

        // Filter model sebelum diproses Trend
        $query = Transaction::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $trend = Trend::query($query);

        if ($startDate && $endDate) {
            $trend = $trend->between(
                start: $startDate,
                end: $endDate,
            );
        }

        $data = $trend->perDay()->sum('total');

        return [
            'datasets' => [
                [
                    'label' => 'Total Transaksi',
                    'data' => $data->map(fn(TrendValue $value) => $value->aggregate),
                ],
            ],
            'labels' => $data->map(fn(TrendValue $value) => Carbon::parse($value->date)->format('d M')),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
