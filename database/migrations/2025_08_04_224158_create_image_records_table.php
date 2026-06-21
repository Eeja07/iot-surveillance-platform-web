<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('image_records', function (Blueprint $table) {
            $table->id();

            // Kolom foreign key yang terhubung ke tabel 'cameras'.
            // onDelete('cascade') berarti jika sebuah kamera dihapus,
            // semua rekaman gambar yang terkait juga akan ikut terhapus.
            $table->foreignId('camera_id')->constrained()->onDelete('cascade');

            // Kolom untuk menyimpan path file gambar di storage.
            $table->string('path');

            // Kolom untuk menyimpan waktu pasti kapan gambar diambil atau diterima.
            $table->timestamp('captured_at');

            // Membuat kolom created_at dan updated_at secara otomatis.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_records');
    }
};
