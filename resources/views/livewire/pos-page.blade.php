<div class="grid grid-cols-1 md:grid-cols-3 gap-4 dark:bg-gray-900">
    <div class="md:col-span-2 bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">

        <form wire:submit="checkout" class="mb-3">
            {{ $this->form }}

            <x-filament::button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded mt-3 cursor-pointer">
                Bayar
            </x-filament::button>
        </form>

        {{-- Kategori --}}
        <div class="overflow-x-auto mb-4 mt-5" style="margin-top: 20px;">
            <div class="flex gap-3 whitespace-nowrap">
                <button wire:click="filterByCategory(null)"
                    class="px-4 py-2 rounded-full flex items-center gap-2
                        {{ is_null($selectedCategory) ? 'text-gray-600 bg-gray-400 shadow dark:text-gray-200 dark:bg-gray-600' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200' }}">
                    <span>Semua</span>
                    <span
                        class="text-xs bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-2 py-0.5 rounded-full">
                        {{ $totalMenuCount }}
                    </span>
                </button>

                @foreach ($categories as $category)
                    <button wire:click="filterByCategory({{ $category->id }})"
                        class="px-4 py-2 rounded-full flex items-center gap-2
                            {{ $selectedCategory === $category->id ? 'text-gray-600 bg-gray-400 shadow dark:text-gray-200 dark:bg-gray-600' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200' }}">
                        <span>{{ $category->name }}</span>
                        <span
                            class="text-xs bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-2 py-0.5 rounded-full">
                            {{ $category->menus_count }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Search --}}
        <div class="mb-4 flex items-center gap-2">
            <input wire:model.live.debounce.300s='search' type="text" placeholder="Cari menu..."
                class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
        </div>

        {{-- Menu --}}
        <div class="flex-grow mb-4">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-4 gap-4 mt-3">
                @forelse ($data as $item)
                    <div wire:click="addToOrder({{ $item->id }})"
                        class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg shadow cursor-pointer hover:ring-2 hover:ring-blue-400 transition">
                        <img src="{{ $item->image_url }}" alt="Gambar Menu"
                            class="w-full h-20 object-cover rounded-lg mb-2">
                        <h3 class="text-sm font-semibold">{{ ucwords($item->name) }}</h3>
                        <div class="flex justify-between">
                            <p class="text-gray-600 dark:text-gray-400 text-xs">{{ $item->category->name }}</p>
                            <p class="text-gray-600 dark:text-gray-400 text-xs">@currency($item->price)</p>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center text-gray-500 dark:text-gray-400 mt-8">
                        <p>Item Menu Kosong.</p>
                    </div>
                @endforelse
            </div>
            <div class="mt-4">
                {{ $data->links() }}
            </div>
        </div>

    </div>

    <div class="md:col-span-1 bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Detail Transaksi</h2>
        <div id="order-list-container" class="flex-1 overflow-y-auto pr-2 mb-4 border-b pb-4">
            @forelse ($transaction_details as $item)
                <div class="mb-4">
                    <div class="flex justify-between items-center bg-gray-100 dark:bg-gray-700 p-4 rounded-lg shadow">
                        <div class="flex items-center">
                            <img src="{{ $item['image_url'] }}" alt="Gambar Menu"
                                class="w-10 h-10 object-cover rounded-lg mr-2">
                            <div class="px-2">
                                <h3 class="text-sm font-semibold">{{ ucwords($item['name']) }}</h3>
                                <p class="text-gray-600 dark:text-gray-400 text-xs">@currency($item['price'])</p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <x-filament::button color="warning"
                                wire:click="decreaseQuantity({{ $item['menu_id'] }})">-</x-filament::button>
                            <span class="px-4">{{ $item['quantity'] }}</span>
                            <x-filament::button color="success"
                                wire:click="increaseQuantity({{ $item['menu_id'] }})">+</x-filament::button>
                        </div>
                    </div>
                </div>
            @empty
                <p id="empty-order-message" class="text-gray-500 text-center mt-4">Transaksi kosong. Silakan
                    tambahkan menu.</p>
            @endforelse
        </div>

        @if (!empty($transaction_details))
            <div class="space-y-2 mb-6">
                <div class="flex justify-between text-lg font-medium text-gray-700 dark:text-gray-300">
                    <span>Subtotal:</span>
                    <span id="subtotal">@currency($subtotal)</span>
                </div>
                <div class="flex justify-between text-lg font-medium text-gray-700 dark:text-gray-300">
                    <span>Diskon ({{ $discountLabel }}):</span>
                    <span id="discount">@currency($discountAmount)</span>
                </div>
                <div class="flex justify-between text-xl font-bold text-gray-900 dark:text-gray-100 border-t pt-2 mt-2">
                    <span>Total Akhir:</span>
                    <span id="final-total">@currency($finalTotal)</span>
                </div>
            </div>
        @endif

        <div class="space-y-4">
            <x-filament::button type="button" wire:click="resetOrder"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded mt-3 cursor-pointer">
                Reset
            </x-filament::button>
        </div>

    </div>
</div>
