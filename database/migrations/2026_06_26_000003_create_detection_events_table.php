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
        Schema::create('detection_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('image_record_id')->constrained('image_records')->onDelete('cascade');
            $table->string('object_class');
            $table->decimal('confidence', 5, 4);
            $table->double('x_min');
            $table->double('y_min');
            $table->double('x_max');
            $table->double('y_max');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detection_events');
    }
};
