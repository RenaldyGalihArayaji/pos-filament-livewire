<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\UserResource\Pages;
use NunoMaduro\Collision\Adapters\Phpunit\State;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\RelationManagers;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'Master Akun';

    protected static ?string $navigationLabel = 'Pengguna';

    protected static ?int $navigationSort = 6;

    protected static ?string $pluralLabel = 'Pengguna';

     protected static ?string $modelLabel = 'Pengguna';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Nama'),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->label('Password') // Label default
                            // Mengatur apakah field ini wajib diisi
                            // Ini akan wajib jika operasi adalah 'create'
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->maxLength(255)
                            // Hashing password sebelum disimpan ke database
                            // Ini penting agar password tersimpan dengan aman
                            ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
                            // Hanya 'dehydrate' (kirim ke model) jika field password diisi.
                            // Ini memastikan password yang sudah ada tidak ditimpa dengan string kosong
                            // jika pengguna tidak memasukkan password baru saat update.
                            ->dehydrated(fn(?string $state): bool => filled($state))
                            // Mengatur autocomplete untuk browser
                            ->autocomplete('new-password')
                            // Menyembunyikan nilai password saat ditampilkan di form edit
                            // (opsional, tergantung preferensi UX Anda)
                            ->revealable(),
                    ]),
                Grid::make(3)
                    ->schema([
                        Forms\Components\Select::make('branch_id')
                            ->required()
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Cabang'),
                        Forms\Components\Select::make('roles')
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->preload()
                            ->label('Roles')
                            ->label('Peran'),
                        Forms\Components\Toggle::make('is_active')
                            ->required()
                            ->label('Status')
                            ->default(true),
                    ]),
                Forms\Components\FileUpload::make('photo')
                    ->image()
                    ->required()
                    ->disk('public')
                    ->maxSize(1024) // 1MB
                    ->directory('image/user_photos')
                    ->preserveFilenames()
                    ->label('Foto'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Foto'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Nama'),
                Tables\Columns\TextColumn::make('branch.name')
                    ->searchable()
                    ->sortable()
                    ->label('Cabang'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->searchable()
                    ->label('Peran')
                    ->badge(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Status'),
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
                //
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
            'index' => Pages\ManageUsers::route('/'),
        ];
    }
}
