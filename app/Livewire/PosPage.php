<?php

namespace App\Livewire;

use Filament\Forms;
use App\Models\Menu;
use App\Models\Table;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Livewire\Component;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Discount;
use Filament\Forms\Form;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use App\Models\PaymentMethod;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;

class PosPage extends Component implements HasForms
{
    use WithPagination, InteractsWithForms;
    public $isCheckoutOpen = false;

    public $search = '';
    public $selectedCategory = null;
    public $transaction_details = [];
    public $name = '';
    public $payment_method_id;
    public $paymentMethod;
    public $note;
    public $is_cash = false;
    public $paid_amount;
    public $change_amount;
    public $status_order = true;
    public $table_id = null;
    public $discount_id = null;
    public $discountAmount = 0;
    public $discountLabel = '-';
    public $finalTotal = 0;
    public $subtotal = 0;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function filterByCategory($categoryId)
    {
        $this->selectedCategory = $categoryId;
        $this->resetPage();
    }

    public function render()
    {
        $userBranchId = Auth::user()->branch_id;

        $categories = Category::withCount(['menus' => function ($query) use ($userBranchId) {
            $query->where('is_active', true)->where('branch_id', $userBranchId);
        }])
            ->whereHas('menus', function ($query) use ($userBranchId) {
                $query->where('is_active', true)->where('branch_id', $userBranchId);
            })
            ->get();

        $totalMenuCount = Menu::where('is_active', true)
            ->where('branch_id', $userBranchId)
            ->count();

        $data = Menu::with('category')
            ->where('is_active', true)
            ->where('branch_id', $userBranchId)
            ->when($this->selectedCategory, fn($q) => $q->where('category_id', $this->selectedCategory))
            ->search($this->search)
            ->orderBy('name')
            ->paginate(12);

        // Pastikan subtotal dihitung terlebih dahulu
        $this->calculationTotalPrice();
        $this->calculateDiscountAndFinalTotal();

        return view('livewire.pos-page', [
            'data' => $data,
            'categories' => $categories,
            'totalMenuCount' => $totalMenuCount,
        ]);
    }

    public function form(Form $form)
    {
        return $form
            ->schema([
                Section::make('Informasi Pesanan')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama')
                                    ->placeholder('Masukkan Nama Pelanggan')
                                    ->required()
                                    ->maxLength(255)
                                    ->default(fn() => $this->name),
                                Forms\Components\Select::make('payment_method_id')
                                    ->label('Metode Pembayaran')
                                    ->options($this->paymentMethod->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $paymentMethod = PaymentMethod::find($state);
                                        $set('is_cash', $paymentMethod?->is_cash ?? false);

                                        // Gunakan 'finalTotal' untuk jumlah yang harus dibayar jika bukan tunai
                                        if (!$paymentMethod?->is_cash) {
                                            $set('change_amount', 0);
                                            $set('paid_amount', $this->finalTotal); // Gunakan $this->finalTotal
                                        } else {
                                            // Reset paid_amount jika beralih ke tunai
                                            $set('paid_amount', null);
                                            $set('change_amount', 0);
                                        }
                                    })
                                    ->afterStateHydrated(function ($state, Set $set, Get $get) {
                                        $paymentMethod = PaymentMethod::find($state);
                                        if (!$paymentMethod?->is_cash) {
                                            $set('paid_amount', $this->finalTotal); // Gunakan $this->finalTotal
                                            $set('change_amount', 0);
                                        }

                                        $set('is_cash', $paymentMethod?->is_cash ?? false);
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
                                Forms\Components\TextInput::make('paid_amount')
                                    ->label('Jumlah Bayar (Rp)')
                                    ->placeholder('Masukkan Jumlah uang')
                                    ->numeric()
                                    ->required()
                                    ->reactive()
                                    ->readOnly(fn(Get $get) => $get('is_cash') === false)
                                    ->default(fn(Get $get) => $get('is_cash') === false ? $this->finalTotal : null)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $change = $state - $this->finalTotal;
                                        $set('change_amount', $change > 0 ? $change : 0);
                                    }),
                                Forms\Components\TextInput::make('change_amount')
                                    ->label('Kembalian')
                                    ->numeric()
                                    ->required()
                                    ->readOnly(),
                            ]),
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Select::make('status_order')
                                    ->required()
                                    ->label('Jenis Pesanan')
                                    ->options([
                                        true => 'Dine-in',
                                        false => 'TakeAway',
                                    ])
                                    ->default(true)
                                    ->live(),
                                Forms\Components\Select::make('table_id')
                                    ->label('Meja')
                                    ->options(fn() => Table::where('status', 'empty')
                                        ->where('branch_id', Auth::user()->branch_id)
                                        ->pluck('table_number', 'id')->toArray())
                                    ->hidden(fn(Get $get) => !$get('status_order'))
                                    ->required(fn(Get $get) => $get('status_order') === true)
                                    ->dehydrated(false)
                                    ->afterStateUpdated(function ($state) {
                                        if ($state) {
                                            $table = Table::find($state);
                                            if ($table) {
                                                $table->update(['status' => 'filled']);
                                            }
                                        }
                                    }),
                                Forms\Components\Select::make('discount_id')
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
                                        $this->discount_id = $state; // Perbarui properti Livewire
                                        $this->calculateDiscountAndFinalTotal(); // Hitung ulang semuanya
                                        // Perbarui paid_amount jika bukan tunai, berdasarkan finalTotal yang baru
                                        if (!$get('is_cash')) {
                                            $set('paid_amount', $this->finalTotal);
                                        }
                                        $set('change_amount', $get('paid_amount') - $this->finalTotal);
                                    }),
                                Forms\Components\TextInput::make('note')
                                    ->label('Catatan'),
                            ]),
                        Forms\Components\Hidden::make('is_cash')->dehydrated(),
                    ])
            ]);
    }

    public function mount()
    {
        if (session()->has('transactionDetails')) {
            $this->transaction_details = session()->get('transactionDetails');
        }

        $this->paymentMethod = PaymentMethod::all();
        $this->calculationTotalPrice(); // Hitung subtotal
        $this->calculateDiscountAndFinalTotal(); // Hitung total akhir dan diskon
        $this->form->fill([
            'total' => $this->finalTotal, // Inisialisasi total form dengan finalTotal
            'paid_amount' => $this->is_cash ? null : $this->finalTotal, // Inisialisasi paid_amount
            'change_amount' => 0,
        ]);
    }

    public function updatedTransactionDetails()
    {
        $this->calculationTotalPrice(); // Hitung ulang subtotal
        $this->calculateDiscountAndFinalTotal(); // Hitung ulang total akhir dan diskon

        // Perbarui bidang formulir
        $this->form->fill([
            'total' => $this->finalTotal,
            'paid_amount' => $this->is_cash ? $this->paid_amount : $this->finalTotal, // Pastikan paid_amount diperbarui berdasarkan is_cash
            'change_amount' => $this->is_cash ? ($this->paid_amount - $this->finalTotal > 0 ? $this->paid_amount - $this->finalTotal : 0) : 0,
        ]);
    }

    public function addToOrder($menuId)
    {
        $menu = Menu::find($menuId);
        if ($menu) {
            $existingItem = null;
            foreach ($this->transaction_details as $key => $item) {
                if ($item['menu_id'] == $menuId) {
                    $existingItem = $key;
                    break;
                }
            }

            if ($existingItem !== null) {
                $this->transaction_details[$existingItem]['quantity']++;
            } else {
                $this->transaction_details[] = [
                    'menu_id' => $menu->id,
                    'name' => $menu->name,
                    'price' => $menu->price,
                    'image_url' => $menu->image_url,
                    'quantity' => 1,
                ];
            }

            session()->put('transactionDetails', $this->transaction_details);
            $this->updatedTransactionDetails();
            Notification::make()
                ->title('Menu Ditambahkan')
                ->body("Menu {$menu->name} telah ditambahkan ke pesanan Anda.")
                ->success()
                ->send();
        }
    }

    public function loadTransactionDetails($transactionDetails)
    {
        $this->transaction_details = $transactionDetails;
        session()->put('transactionDetails', $this->transaction_details);
    }

    public function increaseQuantity($menuId)
    {
        $menu = Menu::find($menuId);
        if (!$menu) {
            Notification::make()
                ->title('Menu Tidak Ditemukan')
                ->body("Menu dengan ID {$menuId} tidak ditemukan.")
                ->danger()
                ->send();
            return;
        }

        foreach ($this->transaction_details as $key => $item) {
            if ($item['menu_id'] == $menuId) {
                // Asumsi tidak ada batasan stok untuk kesederhanaan, jika ada, tambahkan cek stok di sini
                $this->transaction_details[$key]['quantity']++;
                Notification::make()
                    ->title('Jumlah Ditingkatkan')
                    ->body("Jumlah menu {$menu->name} telah ditingkatkan menjadi {$this->transaction_details[$key]['quantity']}.")
                    ->success()
                    ->send();
                break;
            }
        }

        $this->updatedTransactionDetails();
    }

    public function decreaseQuantity($menuId)
    {
        foreach ($this->transaction_details as $key => $item) {
            if ($item['menu_id'] == $menuId) {
                if ($this->transaction_details[$key]['quantity'] > 1) {
                    $this->transaction_details[$key]['quantity']--;
                    Notification::make()
                        ->title('Jumlah Dikurangi')
                        ->body("Jumlah menu {$item['name']} telah dikurangi menjadi {$this->transaction_details[$key]['quantity']}.")
                        ->success()
                        ->send();
                } else {
                    unset($this->transaction_details[$key]);
                    $this->transaction_details = array_values($this->transaction_details);
                    Notification::make()
                        ->title('Menu Dihapus')
                        ->body("Menu {$item['name']} telah dihapus dari pesanan Anda.")
                        ->success()
                        ->send();
                }
                break;
            }
        }

        $this->updatedTransactionDetails();
    }

    public function calculationTotalPrice()
    {
        $this->subtotal = 0; // Reset subtotal
        foreach ($this->transaction_details as $item) {
            $this->subtotal += $item['price'] * $item['quantity']; // Hitung dan masukkan ke subtotal
        }
        // Tidak perlu return, karena sudah memperbarui properti
    }

    public function calculateDiscountAndFinalTotal()
    {
        $this->discountAmount = 0;
        $this->discountLabel = '-';
        $currentSubtotal = $this->subtotal; // Gunakan subtotal yang sudah dihitung dengan benar

        if ($this->discount_id) {
            $discount = Discount::find($this->discount_id);
            if ($discount) {
                $this->discountLabel =
                    $discount->type === 'percentage'
                    ? $discount->value . '%'
                    : '';

                $this->discountAmount =
                    $discount->type === 'percentage'
                    ? $currentSubtotal * ($discount->value / 100)
                    : $discount->value;

                // Pastikan jumlah diskon tidak melebihi subtotal
                $this->discountAmount = min($this->discountAmount, $currentSubtotal);
            }
        }

        $this->finalTotal = max(0, $currentSubtotal - $this->discountAmount);

        // Perbarui bidang 'total' pada form agar 'paid_amount' dapat bereaksi
        $this->form->fill(['total' => $this->finalTotal]);
    }


    public function resetOrder()
    {
        $this->transaction_details = [];
        session()->forget('transactionDetails');

        // Reset semua field form
        $this->name = '';
        // $this->total = 0; // Tidak lagi diperlukan secara langsung
        $this->payment_method_id = null;
        $this->note = null;
        $this->is_cash = false;
        $this->paid_amount = 0;
        $this->change_amount = 0;
        $this->discount_id = null; // Reset diskon
        $this->subtotal = 0; // Reset subtotal
        $this->discountAmount = 0;
        $this->discountLabel = '-';
        $this->finalTotal = 0;

        $this->form->fill([
            'name' => '',
            'total' => 0, // Reset total form
            'payment_method_id' => null,
            'paid_amount' => 0,
            'change_amount' => 0,
            'is_cash' => false,
            'discount_id' => null, // Reset juga di form
        ]);

        Notification::make()
            ->title('Pesanan Direset')
            ->body('Semua detail pesanan telah dikosongkan.')
            ->info()
            ->send();

        $this->dispatch('order-reset');
    }

    public function checkout()
    {
        $this->validate([
            'name' => 'required|max:255',
            'payment_method_id' => 'required|exists:mt_payment_method,id',
            'paid_amount' => 'required|numeric|min:' . $this->finalTotal, // Validasi paid_amount terhadap finalTotal
        ]);

        $customer = Customer::firstOrCreate(['name' => $this->name]);

        $transaction = Transaction::create([
            'order_date' => now(),
            'code' => 'ORD-' . strtoupper(Str::random(8)),
            'total' => $this->finalTotal, // Gunakan finalTotal di sini
            'payment_method_id' => $this->payment_method_id,
            'customer_id' => $customer->id,
            'note' => $this->note,
            'paid_amount' => $this->paid_amount,
            'change_amount' => $this->change_amount,
            'branch_id' => Auth::user()->branch_id,
            'status_order' => $this->status_order,
            'table_id' => $this->table_id,
            'discount_id' => $this->discount_id
        ]);

        foreach ($this->transaction_details as $item) {
            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'menu_id' => $item['menu_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'total_price' => $item['price'] * $item['quantity'],
            ]);
        }

        // Lepaskan meja jika itu adalah pesanan dine-in
        if ($this->status_order && $this->table_id) {
            $table = Table::find($this->table_id);
            if ($table) {
                $table->update(['status' => 'empty']);
            }
        }

        $this->transaction_details = [];
        session()->forget('transactionDetails');

        // Reset semua properti Livewire dan bidang formulir setelah checkout
        $this->resetOrder();

        Notification::make()
            ->title('Pesanan Dibuat')
            ->body("Pesanan Anda telah berhasil dibuat dengan kode {$transaction->code}.")
            ->success()
            ->send();

        return redirect()->route('filament.admin.resources.transactions.index');
    }
}
