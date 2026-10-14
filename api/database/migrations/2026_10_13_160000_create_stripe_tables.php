<?

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stripe_events', function (Blueprint $table) {
             $table->id();
             $table->string('event_id')->unique();
             $table->string('type');
             $table->timestamps();
        });

        Schema::table('bookings', function (Blueprint $table) {
             if (!Schema::hasColumn('bookings', 'payment_status')) {
        $table->string('payment_status')->default('unpaid')->after('status');
             }
        });
     }

     public function down(): void {
        Schema::dropIfExists('stripe_events');
        Schema::table('bookings', function (Blueprint $table) {
             if (Schema::hasColumn('bookings', 'payment_status')) {
        $table->dropColumn('payment_status');
             }
        });
     }
};