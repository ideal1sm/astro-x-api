<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->decimal('items_total', 10, 2)->default(0)->after('status');
            $table->string('delivery_method')->nullable()->after('personal_data_consent_at');
            $table->decimal('delivery_price', 10, 2)->default(0)->after('delivery_method');
            $table->json('delivery_payload')->nullable()->after('delivery_price');
            $table->string('recipient_name')->nullable()->after('delivery_payload');
            $table->string('recipient_phone', 32)->nullable()->after('recipient_name');
            $table->string('delivery_city')->nullable()->after('recipient_phone');
            $table->text('delivery_address')->nullable()->after('delivery_city');
            $table->text('delivery_pickup_point')->nullable()->after('delivery_address');
            $table->text('delivery_comment')->nullable()->after('delivery_pickup_point');
        });
    }

    public function down(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->dropColumn([
                'items_total',
                'delivery_method',
                'delivery_price',
                'delivery_payload',
                'recipient_name',
                'recipient_phone',
                'delivery_city',
                'delivery_address',
                'delivery_pickup_point',
                'delivery_comment',
            ]);
        });
    }
};
