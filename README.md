
<h1>Install Project</h1>

1. Clone repository dari GitHub:
   ```bash
   git clone https://github.com/RenaldyGalihArayaji/pos-filament-livewire.git
   cd pos-filament-livewire
   ```

2. Install dependencies menggunakan Composer:
   ```bash
   composer install
   ```

3. Copy file `.env.example` menjadi `.env`:
   ```bash
   cp .env.example .env
   ```

4. Generate application key:
   ```bash
   php artisan key:generate
   ```

5. Atur konfigurasi database di file `.env`.

6. Jalankan migrasi database:
   ```bash
   php artisan migrate
   ```

7. Jalankan seeder UserSeeder:
   ```bash
   php artisan db:seed --class=UserSeeder
   ```

8. Install Filament:
   ```bash
   composer require filament/filament
   php artisan filament:install
   ```
9. Install Filament Shield:
    ```bash
   composer require bezhansalleh/filament-shield
    ⁠php artisan shield:setup
    php artisan shield:install
    php artisan shield:generate --all
    php artisan shield:super-admin
   ```
10. Jalankan aplikasi:
   ```bash
   php artisan serve
   ```

11. Buka browser dan akses `http://localhost:8000/admin`

