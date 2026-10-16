<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('syllabus')->nullable();
            $table->string('level')->default('beginner');
            $table->decimal('base_price', 10, 2)->default(0);
            $table->decimal('offer_price', 10, 2)->nullable();
            $table->unsignedTinyInteger('duration_months')->default(1);
            $table->decimal('extension_price_month', 10, 2)->nullable();
            $table->boolean('is_published')->default(false);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
