<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuItem;
use Spatie\Permission\Models\Role;

class MenuSeeder extends Seeder
{
  public function run(): void
  {
    // Pastikan role sudah ada
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $editorRole = Role::firstOrCreate(['name' => 'editor']);

    // Hapus data lama untuk menghindari duplikasi
    MenuItem::query()->delete();

    // Buat Menu Utama
    $dashboard = MenuItem::create(['name' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'ti ti-smart-home', 'order' => 1]);
    $patient = MenuItem::create(['name' => 'Patient', 'url' => '#', 'icon' => 'ti ti-users-group', 'order' => 2]);
    $settings = MenuItem::create(['name' => 'Setting', 'url' => '#', 'icon' => 'ti ti-settings', 'order' => 3]);

    // Beri role ke menu utama
    $dashboard->roles()->sync([$adminRole->id, $editorRole->id]);
    $patient->roles()->sync([$adminRole->id, $editorRole->id]);
    $settings->roles()->sync([$adminRole->id]);

    // Buat Submenu untuk Patient
    MenuItem::create(['name' => 'Patient Table', 'url' => '/dashboard/patient/patients', 'parent_id' => $patient->id, 'order' => 1]);
    MenuItem::create(['name' => 'Appointment Table', 'url' => '/dashboard/patient/appointments', 'parent_id' => $patient->id, 'order' => 2]);

    // Buat Submenu untuk Settings
    MenuItem::create(['name' => 'Manage User', 'url' => '/dashboard/settings/users', 'parent_id' => $settings->id, 'order' => 1]);
    MenuItem::create(['name' => 'Manage Role', 'url' => '/dashboard/settings/roles', 'parent_id' => $settings->id, 'order' => 2]);
    MenuItem::create(['name' => 'Manage Menu', 'url' => '/dashboard/settings/menus', 'parent_id' => $settings->id, 'order' => 3]);
  }
}
