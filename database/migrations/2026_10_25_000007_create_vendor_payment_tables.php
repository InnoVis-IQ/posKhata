<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('method', 20);
            $table->timestamp('paid_at');
            $table->foreignId('register_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->softDeletes();

            $table->index(['vendor_id', 'paid_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('vendor_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamp('created_at')->nullable();
            $table->softDeletes();

            $table->index('purchase_invoice_id');
            $table->index('vendor_payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_payment_allocations');
        Schema::dropIfExists('vendor_payments');
    }
};
