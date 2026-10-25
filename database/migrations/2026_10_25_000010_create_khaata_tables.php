<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('khaata_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('amount');
            $table->string('method', 20);
            $table->timestamp('received_at');
            $table->string('receipt_number', 50);
            $table->foreignId('register_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->softDeletes();

            $table->unique(['shop_id', 'receipt_number']);
            $table->index(['customer_id', 'received_at']);
            $table->index('register_session_id');
        });

        Schema::create('khaata_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('amount_delta');
            $table->string('entry_type', 20);
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('reverses_id')->nullable()->constrained('khaata_entries')->nullOnDelete();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->softDeletes();

            $table->index(['customer_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('khaata_entries');
        Schema::dropIfExists('khaata_payments');
    }
};
