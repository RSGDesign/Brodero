<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['percent','fixed']);
            $table->decimal('value',8,2); // percent or RON value
            $table->date('expires_at')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('max_uses')->default(0); // 0 = unlimited
            $table->integer('uses_count')->default(0);
            $table->decimal('min_order_value',10,2)->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('coupons'); }
};