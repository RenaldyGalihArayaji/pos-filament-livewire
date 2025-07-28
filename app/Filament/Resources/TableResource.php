<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\Table as TableModel;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\TableResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\TableResource\RelationManagers;
use App\Filament\Resources\TableResource\Pages\ManageTables;

class TableResource extends Resource
{
    protected static ?string $model = TableModel::class;

    protected static ?string $navigationGroup = 'Master Menu';

    protected static ?string $navigationLabel = 'Meja';

    protected static ?int $navigationSort = 5;

    protected static ?string $pluralLabel = 'Meja';

     protected static ?string $modelLabel = 'Meja';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('branch_id')
                    ->default(fn() => Auth::user()->branch?->id),
                Forms\Components\TextInput::make('table_number')
                    ->required()
                    ->label('No Meja'),
                Forms\Components\TextInput::make('capacity')
                    ->required()
                    ->numeric()
                    ->default(2)
                    ->label('Kapasitas'),
                Forms\Components\Select::make('status')
                    ->required()
                    ->options([
                        'empty' => 'Kosong',
                        'filled' => 'Terisi',
                    ])
                    ->default('empty')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('table_number')
                    ->sortable()
                    ->label('No Meja'),
                Tables\Columns\TextColumn::make('capacity')
                    ->numeric()
                    ->sortable()
                    ->label('Kapasitas'),
                Tables\Columns\TextColumn::make('status')
                    ->searchable()
                    ->colors([
                        'success' => 'empty',
                        'warning' => 'filled',
                    ])
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'empty' => 'Kosong',
                            'filled' => 'Terisi',
                        };
                    }),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->visible(function () {
                        if (Auth::check()) {
                            return Auth::user()->hasRole('super_admin');
                        }
                        return false;
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('created_at')
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
                SelectFilter::make('branch_id')
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
            'index' => Pages\ManageTables::route('/'),
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
