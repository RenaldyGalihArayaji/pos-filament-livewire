<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class PaymentMethodChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Jumlah Pembayaran Berdasarkan Metode';
    protected static string $color = 'warning';


    protected function getData(): array
    {

        $startDate = Carbon::parse($this->filters['start_date'] ?? null);
        $endDate = Carbon::parse($this->filters['end_date'] ?? null);
        $branchId = $this->filters['branch'] ?? null;

        $query = DB::table('dt_transaction')
            ->join('mt_payment_method', 'dt_transaction.payment_method_id', '=', 'mt_payment_method.id')
            ->selectRaw('mt_payment_method.name as metode, SUM(dt_transaction.total) as jumlah');

        if ($startDate && $endDate) {
            $query->whereBetween('order_date', [$startDate, $endDate]);
        }

        if ($branchId) {
            $query->where('dt_transaction.branch_id', $branchId);
        }

        $query->groupBy('mt_payment_method.name');

        $data = $query->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Dibayar',
                    'data' => $data->pluck('jumlah')->toArray(),
                ],
            ],
            'labels' => $data->pluck('metode')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
