<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Models\MenuItem; // <-- Import model

class ShareMenuData
{
  public function handle(Request $request, Closure $next)
  {
    // Gunakan closure agar query tidak berjalan jika tidak dibutuhkan
    View::share('menuData', function () {
      return $this->getFilteredMenu();
    });

    return $next($request);
  }

  private function getFilteredMenu()
  {
    if (!Auth::check()) {
      return []; // Tidak ada menu untuk guest
    }

    $user = Auth::user();

    // Ambil menu utama dan filter berdasarkan role
    $menus = MenuItem::whereNull('parent_id')
      ->with(['children' => function ($query) use ($user) {
        // Filter submenu di level query
        $query->whereHas('roles', function ($subQuery) use ($user) {
          $subQuery->whereIn('name', $user->getRoleNames());
        })->orWhereDoesntHave('roles');
      }, 'roles'])
      ->orderBy('order')
      ->get()
      ->filter(function ($menu) use ($user) {
        // Tampilkan jika menu tidak punya role spesifik ATAU user punya role yang dibutuhkan
        return $menu->roles->isEmpty() || $user->hasAnyRole($menu->roles->pluck('name'));
      });

    // Ubah format agar sesuai dengan yang diharapkan oleh view sidebar Anda
    $formattedMenu = [];
    foreach ($menus as $menu) {
      $formattedMenu[] = (object) [
        'name' => $menu->name,
        'icon' => $menu->icon,
        'url' => $menu->url,
        'slug' => str()->slug($menu->name),
        'submenu' => $menu->children->map(function ($child) {
          return (object) [
            'name' => $child->name,
            'url' => $child->url,
            'slug' => str()->slug($child->name),
          ];
        })->all()
      ];
    }

    return [(object)['menu' => $formattedMenu]];
  }
}
