<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImageRecord extends Model
{
  use HasFactory;

  /**
   * Atribut yang dapat diisi secara massal.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'camera_id',
    'path',
    'captured_at',
    // Anda bisa menambahkan kolom untuk hasil ML di sini nanti
    // 'detection_result' => 'json',
  ];

  /**
   * Tipe data cast untuk atribut.
   *
   * @var array
   */
  protected $casts = [
    'captured_at' => 'datetime',
  ];

  /**
   * Mendefinisikan relasi many-to-one: satu ImageRecord dimiliki oleh satu Camera.
   */
  public function camera(): BelongsTo
  {
    return $this->belongsTo(Camera::class);
  }
}
