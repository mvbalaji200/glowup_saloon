<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Enforce concurrency safety at the database engine level
        Schema::table('bookings', function (Blueprint $table) {
            $table->unique(['stylist_id', 'starts_at'], 'bookings_stylist_starts_at_unique');
        });

        // Simple table for mock confirmation messages
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone');
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('sms_logs');
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique('bookings_stylist_starts_at_unique');
        });
    }
};
