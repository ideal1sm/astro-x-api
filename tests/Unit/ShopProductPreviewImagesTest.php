<?php

namespace Tests\Unit;

use App\Models\ShopProduct;
use App\Models\ShopProductImage;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class ShopProductPreviewImagesTest extends TestCase
{
    public function test_it_returns_primary_image_url(): void
    {
        $product = new ShopProduct(['name' => 'Мёд']);
        $product->setRelation('images', new Collection([
            new ShopProductImage(['path' => 'products/first.jpg']),
            new ShopProductImage(['path' => 'products/second.jpg']),
        ]));

        $this->assertStringContainsString('products/first.jpg', (string) $product->primary_image_url);
    }

    public function test_it_returns_all_preview_image_urls(): void
    {
        $product = new ShopProduct(['name' => 'Мёд']);
        $product->setRelation('images', new Collection([
            new ShopProductImage(['path' => 'products/first.jpg']),
            new ShopProductImage(['path' => 'products/second.jpg']),
        ]));

        $this->assertCount(2, $product->preview_image_urls);
        $this->assertStringContainsString('products/first.jpg', $product->preview_image_urls[0]);
        $this->assertStringContainsString('products/second.jpg', $product->preview_image_urls[1]);
    }

    public function test_it_returns_empty_preview_urls_and_null_primary_when_product_has_no_images(): void
    {
        $product = new ShopProduct(['name' => 'Мёд']);
        $product->setRelation('images', new Collection());

        $this->assertNull($product->primary_image_url);
        $this->assertSame([], $product->preview_image_urls);
    }
}
