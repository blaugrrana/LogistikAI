<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('racks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('zone')->nullable();       // mis. "Fast Moving"
            $table->string('category')->nullable();   // kategori barang yang diperuntukkan
            $table->unsignedInteger('capacity')->default(100);
            $table->timestamps();

            $table->unique(['warehouse_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('racks');
    }
};
