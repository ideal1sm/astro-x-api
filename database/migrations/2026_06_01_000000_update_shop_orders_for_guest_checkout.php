<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('customer_name')->after('total');
            $table->string('customer_phone', 32)->after('customer_name');
            $table->string('customer_email')->after('customer_phone');
            $table->timestamp('personal_data_consent_at')->nullable()->after('customer_email');
        });
    }

    public function down(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->dropColumn([
                'customer_name',
                'customer_phone',
                'customer_email',
                'personal_data_consent_at',
            ]);

            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
