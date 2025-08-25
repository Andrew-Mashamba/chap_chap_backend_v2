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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('pin')->nullable();
            $table->string('firebase_uid')->nullable();
            $table->string('phone_number', 20);
            $table->string('email')->nullable();
            $table->string('full_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name', 300)->nullable();
            $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('id_type', 50)->nullable();
            $table->string('id_number', 100)->nullable();
            $table->string('id_document_path')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('country', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('ward', 100)->nullable();
            $table->string('street')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('shop_name')->nullable();
            $table->text('shop_description')->nullable();
            $table->string('shop_logo')->nullable();
            $table->string('seller_id', 100)->nullable();
            $table->string('upline_id', 100)->nullable();
            $table->integer('seller_level')->default(1);
            $table->string('referral_code', 100)->nullable();
            $table->string('referred_by_code', 100)->nullable();
            $table->boolean('referral_bonus_eligible')->default(true);
            $table->integer('total_downlines')->default(0);
            $table->decimal('total_sales_volume', 15, 2)->default(0.00);
            $table->decimal('commission_balance', 15, 2)->default(0.00);
            $table->timestamp('last_commission_paid_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->boolean('is_team_leader')->default(false);
            $table->boolean('is_blacklisted')->default(false);
            $table->boolean('joined_via_invite')->default(false);
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number', 100)->nullable();
            $table->string('mobile_money_number', 20)->nullable();
            $table->boolean('kyc_verified')->default(false);
            $table->enum('account_status', ['pending', 'active', 'suspended'])->default('pending');
            $table->string('api_token', 150)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('phone_number');
            $table->index('email');
            $table->index('seller_id');
            $table->index('upline_id');
            $table->index('referral_code');
            $table->index('referred_by_code');
            $table->index('account_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
