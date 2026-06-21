<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    // Reset cached roles and permissions
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    // 1. Buat Permissions (Hak Akses)
    // Definisikan hak akses untuk berbagai modul atau fitur
    $permissions = [
      // Hak akses untuk manajemen postingan
      'post-list',
      'post-create',
      'post-edit',
      'post-delete',

      // Hak akses untuk manajemen user (biasanya hanya untuk admin)
      'user-list',
      'user-create',
      'user-edit',
      'user-delete',
    ];

    // Buat permission dari array di atas
    foreach ($permissions as $permission) {
      Permission::firstOrCreate(['name' => $permission]);
    }

    // 2. Buat Roles (Peran) dan berikan hak akses

    // Role 'user' (pengguna biasa, hanya bisa melihat postingan)
    $userRole = Role::firstOrCreate(['name' => 'user']);
    $userRole->givePermissionTo(['post-list']);

    // Role 'editor' (bisa mengelola semua postingan)
    $editorRole = Role::firstOrCreate(['name' => 'editor']);
    $editorRole->givePermissionTo([
      'post-list',
      'post-create',
      'post-edit',
      'post-delete',
    ]);

    // Role 'admin' (akses super, bisa melakukan segalanya)
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    // Berikan semua permission yang ada ke admin
    $adminRole->givePermissionTo(Permission::all());
  }
}
