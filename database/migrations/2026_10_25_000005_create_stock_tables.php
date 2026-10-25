<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('to_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->timestamp('transferred_at');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->softDeletes();
        });

        Schema::create('stock_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty', 12, 3);
            $table->softDeletes();
        });

        Schema::create('stock_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty_delta', 12, 3);
            $table->string('movement_type', 30);
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('reverses_id')->nullable()->constrained('stock_ledger_entries')->nullOnDelete();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->softDeletes();

            $table->index(['branch_id', 'product_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('movement_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledger_entries');
        Schema::dropIfExists('stock_transfer_lines');
        Schema::dropIfExists('stock_transfers');
    }
};
