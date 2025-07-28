<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dt_transaction', function (Blueprint $table) {
            $table->foreignId('discount_id')
                ->nullable()
                ->constrained('mt_discount')
                ->nullOnDelete()
                ->after('payment_method_id'); // atau sesuaikan posisi kolomnya
        });
    }

    public function down(): void
    {
        Schema::table('dt_transaction', function (Blueprint $table) {
            $table->dropForeign(['discount_id']);
            $table->dropColumn('discount_id');
        });
    }
};
