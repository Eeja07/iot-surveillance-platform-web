<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
  use HasFactory;

  /**
   * Atribut yang dapat diisi secara massal.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'user_id',
    'action',
    'description',
  ];

  /**
   * Mendefinisikan relasi many-to-one: satu log aktivitas dilakukan oleh satu User.
   * 'nullable' karena beberapa aksi mungkin dilakukan oleh sistem.
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class)->nullable();
  }
}
