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
        Schema::create('dt_transaction', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_method_id')->nullable()->constrained('mt_payment_method')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('mt_customer')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('mt_branch')->nullOnDelete();
            $table->foreignId('table_id')->nullable()->constrained('mt_table')->nullOnDelete();
            $table->date('order_date')->default(now());
            $table->string('code')->unique();
            $table->integer('total');
            $table->text('note')->nullable();
            $table->integer('paid_amount')->default(0);
            $table->integer('change_amount')->default(0);
            $table->boolean('status_order')->default(true);
            $table->boolean('status_kitchen')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dt_transaction');
    }
};
