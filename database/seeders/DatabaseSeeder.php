<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  // database/seeders/DatabaseSeeder.php

  public function run()
  {
    $this->call([
      RolePermissionSeeder::class, // Jalankan ini dulu untuk membuat roles
      UserSeeder::class,           // Baru jalankan ini untuk membuat user & menghubungkannya ke role
    ]);
  }
}
