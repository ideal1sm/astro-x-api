<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('id')->constrained('product_categories')->nullOnDelete();
            $table->string('name')->after('category_id');
            $table->text('short_description')->nullable()->after('name');

            if (Schema::hasColumn('products', 'zodiac_sign')) {
                $table->dropColumn('zodiac_sign');
            }

            $table->json('zodiac_signs')->nullable()->after('short_description');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['name', 'short_description', 'zodiac_signs', 'category_id']);
            $table->string('zodiac_sign')->nullable();
        });
    }
};
