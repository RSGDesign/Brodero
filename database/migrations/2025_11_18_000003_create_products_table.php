<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->integer('price_cents');
            $table->string('image_url')->nullable();
            $table->string('downloadable_file')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('products'); }
};