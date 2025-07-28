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
        Schema::create('mt_payment_method', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('mt_branch')->onDelete('cascade');
            $table->string('name')->unique();
            $table->boolean('is_cash')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mt_payment_method');
    }
};
