<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use GuzzleHttp\Promise\Create;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Branch::create([
            'name' => 'Cabang Utama',
            'address' => '123 Main St, City, Country',
            'phone' => '123-456-7890',
            'email' => 'cabangutama@gmail.com',
            'is_active' => true,
            ]);

        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@gmail.com',
            'password' => bcrypt('12345678'),
            'branch_id' => 1,
            'photo' => 'superadmin.png',
            'is_active' => true,
        ]);
    }
}
