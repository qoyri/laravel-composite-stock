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
        Schema::table('articles', function (Blueprint $table) {
            $table->string('silhouette', 16)->default('tshirt')->after('material');
        });

        DB::statement("ALTER TABLE articles ADD CONSTRAINT articles_silhouette_check CHECK (silhouette IN ('tshirt', 'long_sleeve', 'tank', 'hoodie', 'sweatshirt', 'bodysuit', 'tote_bag', 'cap'))");
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('silhouette');
        });
    }
};
