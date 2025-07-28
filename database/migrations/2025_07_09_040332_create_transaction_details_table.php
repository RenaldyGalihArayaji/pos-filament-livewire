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
        Schema::create('dt_transaction_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('dt_transaction')->onDelete('cascade');
            $table->foreignId('menu_id')->constrained('mt_menu')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->integer('unit_price');
            $table->integer('total_price');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dt_transaction_detail');
    }
};
