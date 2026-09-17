<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A marking (embroidery, flocking, screen print) is the second stock component.
 * Its stock counts consumables: pre-printed transfers, embroidered patches...
 * Print-on-demand markings are flagged unlimited rather than given a NULL stock:
 * NULL would mean "no limit" in LEAST() but "null" in PHP's min().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('technique', 20);
            $table->string('ink_color', 40);
            $table->char('ink_hex', 7);
            $table->boolean('is_unlimited')->default(false);
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE markings ADD CONSTRAINT markings_stock_check CHECK (stock >= 0)');
        DB::statement("ALTER TABLE markings ADD CONSTRAINT markings_technique_check CHECK (technique IN ('embroidery', 'flocking', 'screen_printing'))");
        DB::statement("ALTER TABLE markings ADD CONSTRAINT markings_ink_hex_check CHECK (ink_hex ~ '^#[0-9A-Fa-f]{6}$')");
    }

    public function down(): void
    {
        Schema::dropIfExists('markings');
    }
};
