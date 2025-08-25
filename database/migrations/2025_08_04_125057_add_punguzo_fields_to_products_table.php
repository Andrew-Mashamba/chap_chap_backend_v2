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
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku')->nullable()->after('name');
            $table->string('barcode')->nullable()->after('sku');
            $table->string('subcategory')->nullable()->after('category');
            $table->string('brand')->nullable()->after('subcategory');
            $table->string('unit')->default('piece')->after('brand');
            $table->decimal('weight', 8, 2)->nullable()->after('unit');
            $table->string('dimensions')->nullable()->after('weight');
            $table->unsignedBigInteger('merchant_id')->nullable()->after('dimensions');
            $table->integer('stock_quantity')->default(0)->after('discount_price');
            $table->integer('min_stock_level')->default(0)->after('stock_quantity');
            $table->integer('max_stock_level')->default(1000)->after('min_stock_level');
            $table->boolean('is_active')->default(true)->after('is_delivery_allowed');
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->json('media')->nullable()->after('is_featured');
            $table->json('tags')->nullable()->after('media');
            
            // Add indexes
            $table->index('sku');
            $table->index('category');
            $table->index('subcategory');
            $table->index('brand');
            $table->index('is_active');
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['sku']);
            $table->dropIndex(['category']);
            $table->dropIndex(['subcategory']);
            $table->dropIndex(['brand']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['is_featured']);
            
            $table->dropColumn([
                'sku',
                'barcode',
                'subcategory',
                'brand',
                'unit',
                'weight',
                'dimensions',
                'merchant_id',
                'stock_quantity',
                'min_stock_level',
                'max_stock_level',
                'is_active',
                'is_featured',
                'media',
                'tags'
            ]);
        });
    }
};
