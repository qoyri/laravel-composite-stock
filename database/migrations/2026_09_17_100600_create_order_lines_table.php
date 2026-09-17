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
        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('article_id');
            $table->unsignedBigInteger('article_variant_id');
            $table->unsignedBigInteger('marking_id');

            // Snapshot: what the customer saw and paid, immune to later catalogue edits.
            $table->string('product_name');
            $table->string('variant_label');
            $table->integer('unit_price_cents');
            $table->integer('quantity');
            $table->integer('line_total_cents');
            // Consumables actually taken at sale time (0 if the marking was
            // unlimited). A cancellation gives back exactly this, even if the
            // product's units_per_item or the marking's unlimited flag changed since.
            $table->integer('marking_units');
            $table->timestamps();

            // The composite keys chain the line's columns together: the variant must
            // belong to the product's article, and the marking must be the product's
            // marking. A line mixing "hoodie variant" with "T-shirt product" is
            // rejected by the database, whatever the application code does.
            $table->foreign(['product_id', 'article_id'])
                ->references(['id', 'article_id'])->on('products')
                ->restrictOnDelete();
            $table->foreign(['product_id', 'marking_id'])
                ->references(['id', 'marking_id'])->on('products')
                ->restrictOnDelete();
            $table->foreign(['article_variant_id', 'article_id'])
                ->references(['id', 'article_id'])->on('article_variants')
                ->restrictOnDelete();

            $table->index('product_id');
            $table->index('article_variant_id');
            $table->index('marking_id');
        });

        DB::statement('ALTER TABLE order_lines ADD CONSTRAINT order_lines_quantity_check CHECK (quantity > 0)');
        DB::statement('ALTER TABLE order_lines ADD CONSTRAINT order_lines_total_check CHECK (unit_price_cents > 0 AND line_total_cents = unit_price_cents * quantity)');
        DB::statement('ALTER TABLE order_lines ADD CONSTRAINT order_lines_marking_units_check CHECK (marking_units >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('order_lines');
    }
};
