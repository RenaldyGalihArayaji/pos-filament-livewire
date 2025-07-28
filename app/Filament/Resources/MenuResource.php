<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Menu;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\MenuResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\MenuResource\RelationManagers;
use App\Models\Category;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static ?string $navigationGroup = 'Master Menu';
    protected static ?string $navigationLabel = 'Menu';

    protected static ?int $navigationSort = 3;
    protected static ?string $pluralLabel = 'Menu';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('branch_id')
                    ->default(fn() => Auth::user()->branch?->id),
                Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name')
                    // ->searchable()
                    // ->preload()
                    ->required()
                    ->label('Kategori')
                    ->options(function () {
                        if (Auth::check()) {
                            $user = Auth::user();
                            $query = Category::query();
                            // if (!$user->hasRole('super_admin')) {
                            // }
                            if ($user->branch_id) {
                                $query->where('branch_id', $user->branch_id);
                            }
                            return $query->pluck('name', 'id')->toArray();
                        }
                        return [];
                    }),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                        $set('slug', Menu::generateSlug($state));
                    })
                    ->live(onBlur: true)
                    ->label('Nama Menu'),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->readOnly(),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('Rp. ')
                    ->label('Harga'),
                Forms\Components\FileUpload::make('image')
                    ->image()
                    ->maxSize(1024)
                    ->disk('public')
                    ->directory('image/menu_images')
                    ->label('Gambar'),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->label('Status')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Gambar'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Nama Menu'),
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable()
                    ->label('Kategori'),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->prefix('Rp.')
                    ->formatStateUsing(fn($state) => $state == floor($state) ? number_format($state, 0, ',', '.') : number_format($state, 2, ',', '.'))
                    ->sortable()
                    ->label('Harga'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Status'),
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
            'index' => Pages\ManageMenus::route('/'),
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
