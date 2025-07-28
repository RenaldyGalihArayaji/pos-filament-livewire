<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            Section::make('')
                ->schema([
                    Select::make('branch')
                    ->options( Branch::all()->pluck('name', 'id'))
                    ->label('Cabang'),
                    DatePicker::make('start_date')
                    ->maxDate(fn(Get $get) => $get('start_date') ? : now())
                    ->label('Tanggal Awal'),
                    DatePicker::make('end_date')
                    ->maxDate(fn(Get $get) => $get('end_date') ? : now())
                    ->label('Tanggal Akhir')
                ])->columns(3)
        ]);
    }

}
