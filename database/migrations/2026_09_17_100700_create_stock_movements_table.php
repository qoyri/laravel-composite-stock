<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only audit log. The stock columns stay the source of truth;
 * this table explains how they got there.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->morphs('stockable');
            $table->integer('delta');
            $table->integer('stock_after');
            $table->string('reason', 16);
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('order_id');
        });

        DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_delta_check CHECK (delta <> 0 AND stock_after >= 0)');
        DB::statement("ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_reason_check CHECK (reason IN ('sale', 'cancellation', 'adjustment'))");
        DB::statement("ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_type_check CHECK (stockable_type IN ('article_variant', 'marking'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
