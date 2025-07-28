<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Models\Transaction;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Console\Concerns\InteractsWithIO;

class MyStatsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $startDate = Carbon::parse($this->filters['start_date'] ?? null);
        $endDate = Carbon::parse($this->filters['end_date'] ?? null);
        $branchId = $this->filters['branch'] ?? null;

        // Base query yang akan digunakan untuk semua perhitungan, sudah termasuk filter cabang
        $baseQuery = Transaction::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId));

        // Hitung total transaksi (berdasarkan jumlah record) dalam rentang tanggal filter
        $totalTransactions = (clone $baseQuery) // Clone query agar tidak mempengaruhi query lain
            ->whereBetween('order_date', [$startDate, $endDate])
            ->count();

        // Hitung total pendapatan (jumlahkan 'total') dalam rentang tanggal filter
        $totalRevenue = (clone $baseQuery) // Clone query
            ->whereBetween('order_date', [$startDate, $endDate])
            ->sum('total');

        // Hitung transaksi hari ini (khusus hari ini, mengabaikan rentang filter tanggal, tapi menghormati filter cabang)
        $transactionsToday = (clone $baseQuery) // Clone query
            ->whereDate('order_date', Carbon::today())
            ->count();

        // Hitung pendapatan hari ini (khusus hari ini, mengabaikan rentang filter tanggal, tapi menghormati filter cabang)
        $revenueToday = (clone $baseQuery) // Clone query
            ->whereDate('order_date', Carbon::today())
            ->sum('total');

        // Untuk contoh chart, kita bisa ambil data transaksi per hari dalam seminggu terakhir
        $chartDataTransactions = [];
        $chartDataRevenue = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);

            // Query untuk data chart juga harus menghormati filter cabang
            $dailyQuery = Transaction::query()
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->whereDate('order_date', $date);

            $count = $dailyQuery->count();
            $sum = $dailyQuery->sum('total');

            $chartDataTransactions[] = $count;
            $chartDataRevenue[] = $sum / 1000000; // Bagi agar angkanya tidak terlalu besar jika ditampilkan di chart
        }

        return [
            Stat::make('Transaksi Hari Ini', number_format($transactionsToday, 0, ',', '.'))
                ->description('Jumlah transaksi yang terjadi hari ini')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning'),
            Stat::make('Pendapatan Hari Ini', 'Rp ' . number_format($revenueToday, 0, ',', '.'))
                ->description('Pendapatan dari transaksi hari ini')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('primary'),
            Stat::make('Total Transaksi', number_format($totalTransactions, 0, ',', '.'))
                ->description('Jumlah seluruh transaksi dalam rentang filter')
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('info')
                ->chart($chartDataTransactions), // Menampilkan grafik jumlah transaksi per hari
            Stat::make('Total Pendapatan', 'Rp ' . number_format($totalRevenue, 0, ',', '.'))
                ->description('Total pendapatan dari semua transaksi dalam rentang filter')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->chart($chartDataRevenue), // Menampilkan grafik pendapatan per hari
        ];
    }
}
