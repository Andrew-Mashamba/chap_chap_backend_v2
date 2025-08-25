<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_id')->unique()->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('merchant_name')->nullable();
            $table->string('pickup_locations')->nullable();
            $table->string('shop_region', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->decimal('selling_price', 10, 2)->nullable();
            $table->decimal('original_price', 10, 2)->nullable();
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->integer('total_item_available')->nullable();
            $table->decimal('within_region_delivery_fee', 10, 2)->nullable();
            $table->decimal('outside_region_delivery_fee', 10, 2)->nullable();
            $table->boolean('is_delivery_allowed')->nullable();
            $table->json('media_json')->nullable();
            $table->json('raw_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
