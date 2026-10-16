<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->onDelete('cascade');
            $table->string('title');
            $table->longText('content')->nullable();
            $table->text('video_url')->nullable();
            $table->unsignedInteger('video_duration_sec')->nullable();
            $table->string('external_video_id')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_free_preview')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
