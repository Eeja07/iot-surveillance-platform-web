<?php

namespace App\Http\Controllers\Pages\Setting;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
  public function __construct()
  {
    // Memastikan hanya Admin yang bisa mengakses fitur manajemen user ini
    $this->middleware(['auth', 'role:admin']);
  }

  /**
   * Menampilkan daftar user dan form (Create/Edit) berdasarkan query parameter.
   */
  public function index(Request $request)
  {
    $users = User::with('roles')->latest()->get();
    $roles = Role::all();

    $showCreateForm = false;
    $showEditForm = false;
    $editingUser = null;
    $userRoles = [];

    // Logika UI: Menampilkan form berdasarkan parameter URL (?action=create atau ?action=edit)
    if ($request->query('action') === 'create') {
      $showCreateForm = true;
    } elseif ($request->query('action') === 'edit' && $request->query('user_id')) {
      $editingUser = User::with('roles')->find($request->query('user_id'));
      if ($editingUser) {
        $showEditForm = true;
        $userRoles = $editingUser->roles->pluck('name')->toArray();
      } else {
        // Diperbaiki: Menggunakan route name yang benar (settings.users.index)
        return redirect()->route('settings.users.index')->with('error', 'User tidak ditemukan.');
      }
    }

    return view('content.pages.settings.manageuser', compact(
      'users', 'roles', 'showCreateForm', 'showEditForm', 'editingUser', 'userRoles'
    ));
  }

  /**
   * Menyimpan user baru (Input manual oleh Admin).
   */
  public function store(Request $request)
  {
    $request->validate([
      'name' => 'required|string|max:255',
      'email' => 'required|string|email|max:255|unique:users',
      'password' => 'required|string|min:8|confirmed',
      'roles' => 'required|string|exists:roles,name',
    ]);

    $user = User::create([
      'name' => $request->name,
      'email' => $request->email,
      'password' => Hash::make($request->password),
      'email_verified_at' => now(), // Admin menambahkan user secara manual, otomatis terverifikasi
    ]);

    $user->assignRole($request->roles);

    // Diperbaiki: Menggunakan route name yang benar (settings.users.index)
    return redirect()->route('settings.users.index')->with('success', 'User berhasil ditambahkan.');
  }

  /**
   * Memperbarui data user.
   */
  public function update(Request $request, User $user)
  {
    $request->validate([
      'name' => 'required|string|max:255',
      'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
      'password' => 'nullable|string|min:8|confirmed',
      'roles' => 'required|string|exists:roles,name',
    ]);

    $user->name = $request->name;
    $user->email = $request->email;

    if ($request->filled('password')) {
      $user->password = Hash::make($request->password);
    }

    $user->save();
    $user->syncRoles($request->roles);

    // Diperbaiki: Menggunakan route name yang benar (settings.users.index)
    return redirect()->route('settings.users.index')->with('success', 'User berhasil diperbarui.');
  }

  /**
   * Menghapus user dengan pengamanan ekstra.
   */
  public function destroy(User $user)
  {
    // 1. Keamanan: Mencegah Admin menghapus akunnya sendiri yang sedang digunakan
    if ($user->id === Auth::id()) {
      return redirect()->route('settings.users.index')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
    }

    // 2. Keamanan Database: Menggunakan try-catch untuk menangani Foreign Key Constraint
    try {
        $user->delete();
        return redirect()->route('settings.users.index')->with('success', 'User berhasil dihapus.');
    } catch (\Exception $e) {
        // Jika user masih punya data kamera/record yang terhubung (Integrity Violation)
        return redirect()->route('settings.users.index')->with('error', 'Gagal menghapus user. Silakan hapus data kamera atau relasi terkait terlebih dahulu.');
    }
  }
}
