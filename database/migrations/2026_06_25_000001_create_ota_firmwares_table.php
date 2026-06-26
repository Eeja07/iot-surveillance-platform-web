<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ota_firmwares', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique();
            $table->string('board')->default('ESP32-CAM');
            $table->string('model')->default('AI_THINKER');
            $table->string('build')->nullable();
            $table->string('min_version')->nullable();
            $table->boolean('mandatory')->default(false);
            $table->boolean('rollback_allowed')->default(true);
            $table->boolean('force')->default(false);
            $table->unsignedBigInteger('size');
            $table->string('sha256')->unique();
            $table->string('url');
            $table->string('path');
            $table->text('release_notes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedInteger('deploy_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ota_firmwares');
    }
};
