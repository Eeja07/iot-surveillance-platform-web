<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at->format('d/m/Y H:i'),

            // PENTING: Menggunakan whenLoaded untuk menghindari error jika relasi 'roles'
            // tidak dimuat atau paket Spatie tidak berfungsi.
            'roles' => $this->whenLoaded('roles', function () {
                // Pastikan model User memiliki trait HasRoles dari Spatie
                return $this->getRoleNames();
            }),
        ];
    }
}
