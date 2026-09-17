<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One row per colour × size of an article. This is the first stock component.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->string('color_name', 40);
            $table->char('color_hex', 7);
            $table->string('size', 8);
            $table->string('sku', 40)->unique();
            $table->integer('stock')->default(0);
            $table->timestamps();

            $table->unique(['article_id', 'color_name', 'size']);
            // Target of the composite foreign key on order_lines: lets the database
            // prove that an ordered variant belongs to the ordered product's article.
            $table->unique(['id', 'article_id']);
        });

        DB::statement('ALTER TABLE article_variants ADD CONSTRAINT article_variants_stock_check CHECK (stock >= 0)');
        DB::statement("ALTER TABLE article_variants ADD CONSTRAINT article_variants_color_hex_check CHECK (color_hex ~ '^#[0-9A-Fa-f]{6}$')");
        DB::statement("ALTER TABLE article_variants ADD CONSTRAINT article_variants_size_check CHECK (size IN ('3M','6M','12M','18M','2A','4A','6A','8A','10A','12A','XS','S','M','L','XL','XXL','3XL','TU'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('article_variants');
    }
};
