<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('color')->nullable()->comment('Цвет изделия');
            $table->string('composition')->nullable()->comment('Состав / материал');
            $table->decimal('price', 10, 2)->default(0)->comment('Цена');
            $table->string('inlay')->nullable()->comment('Вставка (камень)');
            $table->string('lock_type')->nullable()->comment('Вид замка');
            $table->string('length')->nullable()->comment('Длина');
            $table->string('production')->nullable()->comment('Производство');
            $table->string('brand')->nullable()->comment('Бренд');
            $table->string('zodiac_sign')->nullable()->comment('Знак зодиака');
            $table->string('description')->nullable()->comment('Описание');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
