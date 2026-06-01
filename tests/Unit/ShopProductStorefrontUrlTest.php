<?php

namespace Tests\Unit;

use App\Models\ShopCategory;
use App\Models\ShopProduct;
use Tests\TestCase;

class ShopProductStorefrontUrlTest extends TestCase
{
    public function test_it_returns_null_when_storefront_pattern_is_not_configured(): void
    {
        config(['shop.product_url_pattern' => null]);

        $product = new ShopProduct(['name' => 'Мёд']);
        $product->id = 15;
        $product->setRelation('category', new ShopCategory(['slug' => 'honey']));

        $this->assertNull($product->storefront_url);
    }

    public function test_it_builds_storefront_url_from_configured_pattern(): void
    {
        config(['shop.product_url_pattern' => 'https://jewelry-med.ru/shop/{category_slug}/products/{id}']);

        $product = new ShopProduct(['name' => 'Мёд']);
        $product->id = 42;
        $product->setRelation('category', new ShopCategory(['slug' => 'honey']));

        $this->assertSame(
            'https://jewelry-med.ru/shop/honey/products/42',
            $product->storefront_url,
        );
    }
}
