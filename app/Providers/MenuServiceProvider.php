<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Routing\Route;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;


class MenuServiceProvider extends ServiceProvider
{





  public function boot(): void
  {
    $self = $this;  // simpan $this ke variabel lokal

    View::composer('*', function ($view) use ($self) {
      $role = Auth::check() ? (Auth::user()->getRoleNames()->first() ?? 'user') : 'guest';
      // dd($role);

      // Load menu JSON
      $verticalMenuJson = file_get_contents(base_path('resources/menu/verticalMenu.json'));
      $verticalMenuData = json_decode($verticalMenuJson);
      $horizontalMenuJson = file_get_contents(base_path('resources/menu/horizontalMenu.json'));
      $horizontalMenuData = json_decode($horizontalMenuJson);

      // Filter menu berdasarkan role
      $filteredVerticalMenu = $self->filterMenuByRole($verticalMenuData->menu, $role);
      $filteredHorizontalMenu = $self->filterMenuByRole($horizontalMenuData->menu, $role);

      // Share ke view
      $view->with('menuData', [
        (object)['menu' => $filteredVerticalMenu],
        (object)['menu' => $filteredHorizontalMenu],
      ]);
    });
  }




  private function filterMenuByRole(array $menu, string $role): array
  {
    $filteredMenu = [];

    foreach ($menu as $item) {
      // Jika tidak ada key "role", diasumsikan semua role bisa lihat
      $itemRole = $item->role ?? [];

      if (empty($itemRole) || in_array($role, $itemRole)) {
        // Jika ada submenu, filter juga submenu-nya
        if (isset($item->submenu)) {
          $item->submenu = $this->filterMenuByRole($item->submenu, $role);
        }

        $filteredMenu[] = $item;
      }
    }

    return $filteredMenu;
  }
}
