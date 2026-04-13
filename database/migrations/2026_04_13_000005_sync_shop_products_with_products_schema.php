<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shop_products')) {
            return;
        }

        $shouldAddCategoryForeign = false;

        if (! Schema::hasColumn('shop_products', 'category_id')) {
            Schema::table('shop_products', function (Blueprint $table) {
                $table->foreignId('category_id')->nullable()->after('id')->index();
            });

            $shouldAddCategoryForeign = true;
        }

        if (Schema::hasColumn('shop_products', 'shop_category_id')) {
            DB::table('shop_products')
                ->whereNull('category_id')
                ->update(['category_id' => DB::raw('shop_category_id')]);

            Schema::table('shop_products', function (Blueprint $table) {
                $table->dropForeign(['shop_category_id']);
                $table->dropColumn('shop_category_id');
            });

            $shouldAddCategoryForeign = true;
        }

        if ($shouldAddCategoryForeign) {
            Schema::table('shop_products', function (Blueprint $table) {
                $table->foreign('category_id')->references('id')->on('shop_categories')->nullOnDelete();
            });
        }

        Schema::table('shop_products', function (Blueprint $table) {
            if (! Schema::hasColumn('shop_products', 'zodiac_signs')) {
                $table->json('zodiac_signs')->nullable()->after('short_description');
            }

            if (! Schema::hasColumn('shop_products', 'color')) {
                $table->string('color')->nullable()->after('zodiac_signs')->comment('Цвет изделия');
            }

            if (! Schema::hasColumn('shop_products', 'inlay')) {
                $table->string('inlay')->nullable()->after('price')->comment('Вставка (камень)');
            }

            if (! Schema::hasColumn('shop_products', 'lock_type')) {
                $table->string('lock_type')->nullable()->after('inlay')->comment('Вид замка');
            }

            if (! Schema::hasColumn('shop_products', 'length')) {
                $table->string('length')->nullable()->after('lock_type')->comment('Длина');
            }
        });

        if (Schema::hasColumn('shop_products', 'description')) {
            match (DB::connection()->getDriverName()) {
                'pgsql' => DB::statement('ALTER TABLE shop_products ALTER COLUMN description TYPE varchar(255), ALTER COLUMN description DROP NOT NULL'),
                'mysql', 'mariadb' => DB::statement("ALTER TABLE shop_products MODIFY description varchar(255) NULL COMMENT 'Описание'"),
                default => null,
            };
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('shop_products')) {
            return;
        }

        Schema::table('shop_products', function (Blueprint $table) {
            if (Schema::hasColumn('shop_products', 'zodiac_signs')) {
                $table->dropColumn('zodiac_signs');
            }

            if (Schema::hasColumn('shop_products', 'color')) {
                $table->dropColumn('color');
            }

            if (Schema::hasColumn('shop_products', 'inlay')) {
                $table->dropColumn('inlay');
            }

            if (Schema::hasColumn('shop_products', 'lock_type')) {
                $table->dropColumn('lock_type');
            }

            if (Schema::hasColumn('shop_products', 'length')) {
                $table->dropColumn('length');
            }
        });
    }
};
