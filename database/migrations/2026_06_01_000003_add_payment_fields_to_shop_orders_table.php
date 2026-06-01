<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_orders', function (Blueprint $table): void {
            $table->string('payment_method')->nullable()->after('status');
            $table->string('payment_status')->nullable()->after('payment_method');
            $table->string('yookassa_payment_id')->nullable()->after('payment_status')->index();
            $table->decimal('payment_amount', 10, 2)->nullable()->after('total');
            $table->text('payment_confirmation_url')->nullable()->after('payment_amount');
            $table->timestamp('payment_initiated_at')->nullable()->after('payment_confirmation_url');
            $table->timestamp('payment_paid_at')->nullable()->after('payment_initiated_at');
            $table->text('payment_failure_reason')->nullable()->after('payment_paid_at');
            $table->json('payment_payload')->nullable()->after('payment_failure_reason');
        });
    }

    public function down(): void
    {
        Schema::table('shop_orders', function (Blueprint $table): void {
            $table->dropIndex(['yookassa_payment_id']);
            $table->dropColumn([
                'payment_method',
                'payment_status',
                'yookassa_payment_id',
                'payment_amount',
                'payment_confirmation_url',
                'payment_initiated_at',
                'payment_paid_at',
                'payment_failure_reason',
                'payment_payload',
            ]);
        });
    }
};
