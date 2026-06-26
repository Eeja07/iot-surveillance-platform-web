<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ota_deployments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('firmware_id')->constrained('ota_firmwares')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status')->default('Pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->integer('rollout_percentage')->default(100);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('deployment_cameras', function (Blueprint $table) {
            $table->id();
            $table->uuid('deployment_id');
            $table->foreignId('camera_id')->constrained()->onDelete('cascade');
            $table->string('old_version')->nullable();
            $table->string('target_version');
            $table->string('status')->default('Pending');
            $table->integer('progress')->default(0);
            $table->text('message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->foreign('deployment_id')->references('id')->on('ota_deployments')->onDelete('cascade');
            $table->unique(['deployment_id', 'camera_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployment_cameras');
        Schema::dropIfExists('ota_deployments');
    }
};
