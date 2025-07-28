<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Discount;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\DiscountResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\DiscountResource\RelationManagers;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static ?string $navigationGroup = 'Master Menu';

    protected static ?string $navigationLabel = 'Diskon';

    protected static ?int $navigationSort = 2;

    protected static ?string $pluralLabel = 'Diskon';

    protected static ?string $modelLabel = 'Diskon';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('branch_id')
                    ->default(fn() => Auth::user()->branch?->id),
                Forms\Components\TextInput::make('name')
                    ->label('Nama Diskon')
                    ->placeholder('Contoh: Diskon Akhir Tahun')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->label('Tipe Diskon')
                    ->required()
                    ->options([
                        'percentage' => 'Persentase (%)',
                        'fixed' => 'Nominal Tetap (Rp)',
                    ])
                    ->default('percentage'),
                Forms\Components\TextInput::make('value')
                    ->label('Nilai Diskon')
                    ->required()
                    ->numeric()
                    ->minValue(0),
                Forms\Components\Toggle::make('is_active')
                    ->label('Status Aktif')
                    ->inline(false)
                    ->default(true),
                Forms\Components\DatePicker::make('start_date')
                    ->label('Tanggal Mulai'),
                Forms\Components\DatePicker::make('end_date')
                    ->label('Tanggal Berakhir'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Diskon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn(string $state) => $state === 'percentage' ? 'info' : 'success'),
                Tables\Columns\TextColumn::make('value')
                    ->label('Nilai')
                    ->numeric()
                    ->formatStateUsing(
                        fn($state) =>
                        $state == floor($state)
                            ? number_format($state, 0, ',', '.')
                            : number_format($state, 2, ',', '.')
                    )
                    ->prefix(fn($record) => $record->type === 'fixed' ? 'Rp. ' : '')
                    ->suffix(fn($record) => $record->type === 'percentage' ? '%' : '')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Berakhir')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->sortable()
                    ->searchable()
                     ->visible(function () {
                        if (Auth::check()) {
                            return Auth::user()->hasRole('super_admin');
                        }
                        return false;
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status Aktif')
                    ->options([
                        true => 'Aktif',
                        false => 'Nonaktif',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->placeholder(fn($state): string => 'YYYY-MM-DD'),
                        \Filament\Forms\Components\DatePicker::make('to')
                            ->label('Sampai Tanggal')
                            ->placeholder(fn($state): string => 'YYYY-MM-DD'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->visible(function (): bool {
                        if (Auth::check()) {
                            $user = Auth::user();
                            return $user->hasRole('super_admin');
                        }
                        return false;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDiscounts::route('/'),
        ];
    }

     public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $query = parent::getEloquentQuery();

        if (Auth::check()) {
            $user = Auth::user();
            if (!$user->hasRole('super_admin')) {
                if ($user->branch_id) {
                    $query->where('branch_id', $user->branch_id);
                }
            }
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }
}
