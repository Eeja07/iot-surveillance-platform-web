<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class adminseeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    // 1. Buat Role 'admin' jika belum ada
    // Menggunakan firstOrCreate untuk menghindari duplikasi role jika seeder dijalankan berkali-kali.
    $adminRole = Role::firstOrCreate(['name' => 'admin']);

    // 2. Buat User Admin
    // Menggunakan firstOrCreate untuk mencari user berdasarkan email.
    // Jika tidak ditemukan, user baru akan dibuat.
    $adminUser = User::firstOrCreate(
      ['email' => 'admin@ahmad.com'], // Kunci unik untuk mencari user
      [
        'name' => 'Administrator',
        'password' => Hash::make('password'), // Ganti 'password' dengan password yang aman
      ]
    );

    // 3. Berikan role 'admin' ke user tersebut
    // Menggunakan assignRole untuk memberikan role yang sudah dibuat sebelumnya.
    $adminUser->assignRole($adminRole);
  }
}
