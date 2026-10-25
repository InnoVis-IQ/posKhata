<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['shop_id', 'code']);
            $table->index(['shop_id', 'name']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'name']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->string('name');
            $table->string('sku', 100)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->bigInteger('unit_cost')->default(0);
            $table->bigInteger('unit_sale_price')->default(0); // tax-inclusive
            $table->bigInteger('tax')->default(0); // tax amount in minor units; default from shop tax_rate_bps, overridable
            $table->bigInteger('unit_discount')->default(0);
            $table->bigInteger('final_unit_price')->default(0); // unit_sale_price - unit_discount
            $table->decimal('batch_size', 12, 3)->default(0); // 0 = no batch deal
            $table->bigInteger('batch_discount')->default(0); // off per full batch of batch_size
            $table->decimal('reorder_level', 12, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['shop_id', 'sku']);
            $table->unique(['shop_id', 'barcode']);
            $table->index(['shop_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('units');
    }
};
