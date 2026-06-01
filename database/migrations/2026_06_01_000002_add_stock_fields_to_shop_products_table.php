<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->string('availability_status')->default('in_stock')->after('price');
            $table->unsignedInteger('stock_quantity')->nullable()->after('availability_status');
        });
    }

    public function down(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropColumn([
                'availability_status',
                'stock_quantity',
            ]);
        });
    }
};
