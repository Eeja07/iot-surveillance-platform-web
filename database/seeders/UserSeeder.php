<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    // Membuat User Admin
    $normalUser = User::firstOrCreate(
      ['email' => 'user@gmail.com'],
      [
        'name' => 'user',
        'password' => Hash::make('password'),
      ]
    );
    // Memberikan role 'admin' ke user tersebut
    $normalUser->assignRole('user');
  }
}
