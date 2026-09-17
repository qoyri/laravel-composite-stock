<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The sellable product: the article × marking pivot, carrying its own data
 * (price of the pair, consumables used per item). Availability is NOT stored here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->restrictOnDelete();
            $table->foreignId('marking_id')->constrained()->restrictOnDelete();
            $table->string('slug')->unique();
            $table->integer('price_cents');
            $table->smallInteger('units_per_item')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['article_id', 'marking_id']);
            $table->index('marking_id');
            // Targets of the composite foreign keys on order_lines.
            $table->unique(['id', 'article_id']);
            $table->unique(['id', 'marking_id']);
        });

        DB::statement('ALTER TABLE products ADD CONSTRAINT products_price_check CHECK (price_cents > 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_units_per_item_check CHECK (units_per_item >= 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
