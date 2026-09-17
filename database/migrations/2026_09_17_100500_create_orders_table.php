<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 16)->unique();
            $table->string('status', 16)->default('confirmed');

            $table->string('email');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone', 32)->nullable();
            $table->string('address_line');
            $table->string('postal_code', 10);
            $table->string('city');
            $table->char('country', 2)->default('CH');

            $table->integer('subtotal_cents');
            $table->integer('shipping_cents');
            $table->integer('total_cents');

            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status IN ('confirmed', 'cancelled'))");
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_amounts_check CHECK (subtotal_cents > 0 AND shipping_cents >= 0 AND total_cents = subtotal_cents + shipping_cents)');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_cancellation_check CHECK ((status = 'cancelled') = (cancelled_at IS NOT NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
