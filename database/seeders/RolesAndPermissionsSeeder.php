<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User; // Pastikan Anda memiliki model User
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    // Reset cached roles and permissions
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // 1. Buat Role
    // Gunakan firstOrCreate untuk menghindari duplikasi jika seeder dijalankan berkali-kali
    $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $perawatRole = Role::firstOrCreate(['name' => 'Perawat', 'guard_name' => 'web']);
    $dokterRole = Role::firstOrCreate(['name' => 'Dokter', 'guard_name' => 'web']);

    // Jika Anda memiliki permissions, Anda bisa membuatnya di sini
    // Contoh:
    // $manageUsersPermission = Permission::firstOrCreate(['name' => 'manage users', 'guard_name' => 'web']);
    // $viewReportsPermission = Permission::firstOrCreate(['name' => 'view reports', 'guard_name' => 'web']);

    // Berikan permissions ke role (opsional, jika Anda menggunakan permissions)
    // $adminRole->givePermissionTo($manageUsersPermission);
    // $adminRole->givePermissionTo($viewReportsPermission);

    // 2. Buat User Admin
    // Buat user admin jika belum ada
    $adminUser = User::firstOrCreate(
      ['email' => 'admin@example.com'], // Cek berdasarkan email
      [
        'name' => 'Admin Utama',
        'password' => Hash::make('password'), // Ganti 'password' dengan password yang lebih kuat di produksi
        'email_verified_at' => now(), // Tandai email sudah diverifikasi
      ]
    );

    // Berikan role 'Admin' kepada user admin
    // syncRoles akan menghapus role lain yang mungkin dimiliki user dan hanya memberikan role yang ditentukan
    $adminUser->syncRoles(['admin']);

    // Anda bisa menambahkan user lain di sini jika diperlukan
    // $perawatUser = User::firstOrCreate(
    //     ['email' => 'perawat@example.com'],
    //     [
    //         'name' => 'Perawat Satu',
    //         'password' => Hash::make('password'),
    //         'email_verified_at' => now(),
    //     ]
    // );
    // $perawatUser->syncRoles(['Perawat']);

    $this->command->info('Roles and Admin user seeded successfully!');
  }
}
