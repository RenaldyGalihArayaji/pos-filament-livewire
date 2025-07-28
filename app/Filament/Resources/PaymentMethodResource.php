<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\PaymentMethod;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PaymentMethodResource\Pages;
use App\Filament\Resources\PaymentMethodResource\RelationManagers;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static ?string $navigationGroup = 'Master Menu';

    protected static ?string $navigationLabel = 'Metode Pembayaran';

    protected static ?int $navigationSort = 4;

    protected static ?string $pluralLabel = 'Metode Pembayaran';

     protected static ?string $modelLabel = 'Metode Pembayaran';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('branch_id')
                    ->default(fn() => Auth::user()->branch?->id),
                Forms\Components\FileUpload::make('icon')
                    ->label('Icon')
                    ->image()
                    ->maxSize(1024)
                    ->disk('public')
                    ->directory('image/payment-methods'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Jenis Metode'),
                Forms\Components\Toggle::make('is_cash')
                    ->required()
                    ->label('Status Tunai'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Jenis Metode'),
                Tables\Columns\IconColumn::make('is_cash')
                    ->boolean()
                    ->label('Status Tunai'),
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
            'index' => Pages\ManagePaymentMethods::route('/'),
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
