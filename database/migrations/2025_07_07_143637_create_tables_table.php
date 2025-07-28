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
        Schema::create('mt_table', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('mt_branch')->onDelete('cascade');
            $table->string('table_number');
            $table->integer('capacity')->default(2);
            $table->enum('status', ['empty', 'filled'])->default('empty');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mt_table');
    }
};
