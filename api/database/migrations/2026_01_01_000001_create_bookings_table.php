<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('stylist_id')->index();
            $table->unsignedBigInteger('service_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status')->default('pending')->index();
            $table->string('payment_status')->default('unpaid')->index();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            // Conflict index: Stylist schedule lookup
            $table->index(['stylist_id', 'starts_at', 'ends_at'], 'idx_stylist_schedule');
        });
    }

    public function down(): void {
        Schema::dropIfExists('bookings');
    }
};
