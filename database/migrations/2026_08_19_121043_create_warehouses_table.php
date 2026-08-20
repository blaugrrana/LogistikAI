<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('focus')->nullable();     // fokus kategori hasil usulan AI
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
