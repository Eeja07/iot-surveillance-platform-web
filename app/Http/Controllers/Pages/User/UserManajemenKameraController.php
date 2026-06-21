<?php

namespace App\Http\Controllers\Pages\User;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserManajemenKameraController extends Controller
{
  /**
   * Menampilkan halaman utama manajemen kamera (yang berisi DataTable).
   */
  public function index()
  {
    return view('content.pages.User.manajemen-kamera', ['view' => 'index']);
  }

  /**
   * [BARU] Menyediakan data untuk Server-Side DataTable.
   */
  public function getData(Request $request)
  {
    $query = Auth::user()->cameras();

    // Handle pencarian
    if ($request->filled('search.value')) {
      $searchValue = $request->input('search.value');
      $query->where(function ($q) use ($searchValue) {
        $q->where('name', 'like', '%' . $searchValue . '%')
          ->orWhere('device_id', 'like', '%' . $searchValue . '%');
      });
    }

    $totalRecords = $query->count();

    // Handle sorting
    $orderColumnIndex = $request->input('order.0.column');
    $orderDir = $request->input('order.0.dir');
    $columns = $request->input('columns');
    if (isset($columns[$orderColumnIndex]['name'])) {
      $query->orderBy($columns[$orderColumnIndex]['name'], $orderDir);
    }

    // Handle pagination
    $cameras = $query->skip($request->input('start'))
      ->take($request->input('length'))
      ->get();

    $data = [];
    foreach ($cameras as $camera) {
      $data[] = [
        'id' => $camera->id,
        'name' => $camera->name,
        'device_id' => Str::limit($camera->device_id, 13) . '...',
        'is_active' => $camera->is_active,
        'action' => view('content.pages.User._partials.camera-actions', ['camera' => $camera])->render(),
      ];
    }

    return response()->json([
      'draw' => intval($request->input('draw')),
      'recordsTotal' => $totalRecords,
      'recordsFiltered' => $totalRecords,
      'data' => $data,
    ]);
  }

  public function edit($id)
  {
    $camera = Camera::findOrFail($id);
    $this->authorize('update', $camera);
    return view('content.pages.User.manajemen-kamera', [
      'view' => 'edit',
      'camera' => $camera
    ]);
  }

  public function update(Request $request, $id)
  {
    $camera = Camera::findOrFail($id);
    $this->authorize('update', $camera);
    $request->validate([
      'name' => 'required|string|max:255',
      'description' => 'nullable|string',
    ]);
    $camera->update($request->only('name', 'description'));
    return redirect()->route('user.my-cameras.index')->with('success', 'Data kamera berhasil diperbarui.');
  }

  public function destroy($id)
  {
    $camera = Camera::findOrFail($id);
    $this->authorize('delete', $camera);
    $admin = User::role('admin')->first();
    $camera->user_id = $admin ? $admin->id : null;
    $camera->save();
    return redirect()->route('user.my-cameras.index')->with('success', 'Kamera berhasil dilepaskan dari akun Anda.');
  }
}
