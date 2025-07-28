<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Menu;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Discount;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Models\PaymentMethod;
use App\Models\Table as MTable;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\TransactionResource\Pages;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Transaksi';

    protected static ?string $pluralLabel = 'Transaksi';

    protected static ?string $modelLabel = 'Transaksi';

    public static function getNavigationBadge(): ?string
    {
        if (Auth::check()) {
            $user = Auth::user();
            $modelClass = static::getModel();
            $query = $modelClass::query();
            if (!$user->hasRole('super_admin')) {
                if ($user->branch_id) {
                    $query->where('branch_id', $user->branch_id);
                }
            }
            return (string) $query->count();
        } else {
            return null;
        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('branch_id')
                    ->default(fn() => Auth::user()->branch?->id),
                Forms\Components\Hidden::make('order_date')
                    ->default(now()),
                Section::make('Data Pelanggan')
                    ->relationship('customer')
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $set('name', $get('name'));
                        $set('email', $get('email'));
                        $set('phone', $get('phone'));
                    })
                    ->columns(1)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->label('Nama'),
                                Forms\Components\TextInput::make('email')
                                    ->email(),
                                Forms\Components\TextInput::make('phone')
                                    ->tel()
                                    ->numeric()
                                    ->label('Nomor Telepon')
                            ]),
                    ]),
                Section::make('Detail Transaksi')
                    ->columns(1)
                    ->schema([
                        self::getItemRepeater(),
                    ]),
                Grid::make()
                    ->schema([
                        Section::make('Total Pemesanan')
                            ->columns(2)
                            ->schema([
                                TextInput::make('code')
                                    ->label('Kode Pesanan')
                                    ->readOnly()
                                    ->default(fn() => 'ORD-' . Str::upper(Str::random(8, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'))),
                                TextInput::make('total')
                                    ->label('Total Pemesanan')
                                    ->readOnly()
                                    ->numeric()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) { // Add this afterStateUpdated
                                        self::updateChange($set, $get);
                                    }),
                                Textarea::make('note')
                                    ->label('Catatan')
                                    ->columnSpanFull(),

                            ]),
                    ]),
                Grid::make()
                    ->schema([
                        Section::make('Informasi Pembayaran')
                            ->columns(3)
                            ->schema([

                                Select::make('payment_method_id')
                                    ->label('Metode Pembayaran')
                                    ->relationship('paymentMethod', 'name')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $paymentMethod = PaymentMethod::find($state);
                                        $set('is_cash', $paymentMethod?->is_cash ?? false);

                                        if (!$paymentMethod?->is_cash) {
                                            $set('change_amount', 0);
                                            $set('paid_amount', $get('total'));
                                        }
                                    })
                                    ->afterStateHydrated(function ($state, Set $set, Get $get) {
                                        $paymentMethod = PaymentMethod::find($state);
                                        $set('is_cash', $paymentMethod?->is_cash ?? false);

                                        if (!$paymentMethod?->is_cash) {
                                            $set('paid_amount', $get('total'));
                                            $set('change_amount', 0);
                                        }
                                    })
                                    ->options(function () {
                                        if (Auth::check()) {
                                            $user = Auth::user();
                                            $query = PaymentMethod::query();
                                            if ($user->branch_id) {
                                                $query->where('branch_id', $user->branch_id);
                                            }
                                            return $query->pluck('name', 'id')->toArray();
                                        }
                                        return [];
                                    }),
                                Hidden::make('is_cash')
                                    ->dehydrated(),
                                TextInput::make('paid_amount')
                                    ->label('Jumlah Dibayar')
                                    ->numeric()
                                    ->reactive()
                                    ->readOnly(fn(Get $get) => $get('is_cash') === false)
                                    ->default(function (Get $get) {
                                        return $get('is_cash') === false ? $get('total') ?? 0 : null;
                                    })
                                    ->afterStateUpdated(function (Set $set, Get $get) { // Add this afterStateUpdated
                                        self::updateChange($set, $get);
                                    }),
                                TextInput::make('change_amount')
                                    ->label('Jumlah Kembalian')
                                    ->numeric()
                                    ->readOnly()
                                    ->default(0),
                                Select::make('status_order')
                                    ->required()
                                    ->label('Jenis Pesanan')
                                    ->options([
                                        true => 'Dine-in',
                                        false => 'TakeAway',
                                    ])
                                    ->default(true)
                                    ->live(),
                                Select::make('table_id')
                                    ->label('Meja')
                                    ->relationship('table', 'table_number', fn(Builder $query) => $query->where('status', 'empty'))
                                    ->hidden(fn(Get $get) => $get('status_order') == false)
                                    ->required(fn(Get $get) => $get('status_order') == true)
                                    ->dehydrated(false)
                                    ->afterStateUpdated(function ($state) {
                                        if ($state) {
                                            $table = MTable::find($state);
                                            if ($table) {
                                                $table->update(['status' => 'filled']);
                                            }
                                        }
                                    }),
                                Select::make('discount_id')
                                    ->label('Diskon')
                                    ->options(function () {
                                        return Discount::where('is_active', true)
                                            ->where('branch_id', Auth::user()->branch_id)
                                            ->where(function ($query) {
                                                $today = now()->toDateString();
                                                $query->whereNull('start_date')
                                                    ->orWhere('start_date', '<=', $today);
                                                $query->whereNull('end_date')
                                                    ->orWhere('end_date', '>=', $today);
                                            })
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    })
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $discount = Discount::where('branch_id', Auth::user()->branch_id)->find($state);
                                        $total = collect($get('transactionDetails'))->filter(fn($item) => !empty($item['menu_id']) && !empty($item['quantity']))->reduce(function ($total, $menu) use ($get) {
                                            $menuPrice = Menu::find($menu['menu_id'])->price ?? 0;
                                            return $total + ($menuPrice * $menu['quantity']);
                                        }, 0);

                                        if ($discount) {
                                            $value = $discount->value;
                                            $discounted = $discount->type === 'percentage'
                                                ? $total - ($total * ($value / 100))
                                                : max(0, $total - $value);

                                            $set('total', $discounted);
                                        } else {
                                            $set('total', $total);
                                        }

                                        if (!$get('is_cash')) {
                                            $set('paid_amount', $get('total'));
                                        }
                                        self::updateChange($set, $get);
                                    }),

                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->label('Kode'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->numeric()
                    ->sortable()
                    ->label('Nama'),
                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->label('Metode Pembayaran')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn($record) => match ($record->paymentMethod->id ?? null) {
                        1 => 'success',
                        2 => 'danger',
                        3 => 'warning',
                        default => 'primary',
                    }),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->sortable()
                    ->prefix('Rp.')
                    ->formatStateUsing(fn($state) => $state == floor($state) ? number_format($state, 0, ',', '.') : number_format($state, 2, ',', '.')),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Jumlah Bayar')
                    ->numeric()
                    ->sortable()
                    ->prefix('Rp.')
                    ->formatStateUsing(fn($state) => $state == floor($state) ? number_format($state, 0, ',', '.') : number_format($state, 2, ',', '.')),
                Tables\Columns\TextColumn::make('change_amount')
                    ->label('Jumlah Kembalian')
                    ->numeric()
                    ->sortable()
                    ->prefix('Rp.')
                    ->formatStateUsing(fn($state) => $state == floor($state) ? number_format($state, 0, ',', '.') : number_format($state, 2, ',', '.')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('discount_summary')
                    ->label('Diskon')
                    ->getStateUsing(
                        fn($record) =>
                        $record->discount
                            ? $record->discount->name . ' (' .
                            ($record->discount->type === 'percentage'
                                ? $record->discount->value . '%'
                                : 'Rp ' . number_format($record->discount->value, 0, ',', '.')) . ')'
                            : '-'
                    )
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->visible(function () {
                        if (Auth::check()) {
                            return Auth::user()->hasRole('super_admin');
                        }
                        return false;
                    }),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
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
                SelectFilter::make('discount_id')
                    ->label('Diskon')
                    ->relationship('discount', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('print')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn(Transaction $record): string => route('transactions.print', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\ViewAction::make()->modalHeading('Detail Menu')
                ])
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
            'index' => Pages\ManageTransactions::route('/'),
        ];
    }

    public static function getItemRepeater(): Repeater
    {
        return Repeater::make('transactionDetails')
            ->label('Data menu')
            ->relationship()
            ->live()
            ->afterStateUpdated(function (Get $get, Set $set) {
                self::updateTotalPrice($get, $set);
            })
            ->columns([
                'md' => 12
            ])
            ->schema([
                Select::make('menu_id')
                    ->label('Menu')
                    ->searchable()
                    ->required()
                    ->options(function () {
                        if (Auth::check()) {
                            $user = Auth::user();
                            $query = Menu::query()->where('is_active', true);
                            // if (!$user->hasRole('super_admin')) {
                            // }
                            if ($user->branch_id) { // Pastikan branch_id ada
                                $query->where('branch_id', $user->branch_id);
                            }
                            return $query->pluck('name', 'id')->toArray();
                        }
                        return [];
                    })
                    ->columnSpan([
                        'md' => 4
                    ])
                    ->afterStateHydrated(function ($state, Set $set) {
                        $menu = Menu::find($state);
                        $set('unit_price', $menu->price ?? 0);
                    })
                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                        $item = Menu::find($state);
                        $set('unit_price', $item->price ?? 0);
                        $set('total_price', ($item->price ?? 0) * ($get('quatity') ?? 1));
                        $get('quantity') ?? 1;
                        self::updateTotalPrice($get, $set);
                    })
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                TextInput::make('quantity')
                    ->label('Jumlah')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->columnSpan(['md' => 2])
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        $set('total_price', ($get('unit_price') ?? 0) * $state);
                        self::updateTotalPrice($get, $set);
                    }),
                TextInput::make('unit_price')
                    ->label('Harga')
                    ->required()
                    ->numeric()
                    ->readOnly()
                    ->columnSpan(['md' => 3]),
                TextInput::make('total_price')
                    ->label('Total')
                    ->required()
                    ->numeric()
                    ->readOnly()
                    ->live()
                    ->columnSpan(['md' => 3])
            ]);
    }

    public static function updateTotalPrice(Get $get, Set $set): Void
    {
        $menus = collect($get('transactionDetails'))->filter(fn($item) => !empty($item['menu_id']) && !empty($item['quantity']));

        $prices = Menu::find($menus->pluck('menu_id'))->pluck('price', 'id');

        $total = $menus->reduce(function ($total, $menu) use ($prices) {
            return $total + ($prices[$menu['menu_id']] * $menu['quantity']);
        }, 0);

        $set('total', $total);
    }

    public static function updateChange(Set $set, Get $get): void
    {
        $paidAmount = (int) $get('paid_amount') ?? 0;
        $total = (int) $get('total') ?? 0;
        $changepaid = $paidAmount - $total;
        $set('change_amount', $changepaid);
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
