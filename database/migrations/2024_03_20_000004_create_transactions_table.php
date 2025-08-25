<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->string('type'); // deposit, withdrawal, commission
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->nullable();
            $table->string('status')->default('pending');
            $table->string('reference_number')->nullable();
            $table->text('description')->nullable();
            $table->json('payment_details')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
