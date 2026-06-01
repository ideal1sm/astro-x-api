<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class OpenApiContractTest extends TestCase
{
    public function test_openapi_json_is_valid(): void
    {
        $document = $this->decodeOpenApi();

        $this->assertSame('3.1.0', $document['openapi']);
        $this->assertIsArray($document['paths']);
        $this->assertIsArray($document['components']);
    }

    public function test_shop_order_contract_contains_actual_checkout_fields(): void
    {
        $document = $this->decodeOpenApi();
        $schema = $document['paths']['/shop/orders']['post']['requestBody']['content']['application/json']['schema'];

        $this->assertContains('personal_data_consent', $schema['required']);
        $this->assertContains('payment_method', $schema['required']);
        $this->assertContains('payment_return_url', $schema['required']);
        $this->assertContains('delivery_method', $schema['required']);
        $this->assertContains('delivery_payload', $schema['required']);
        $this->assertArrayHasKey('delivery_pickup_point', $schema['properties']);
        $this->assertArrayHasKey('delivery_address', $schema['properties']);

        $responseSchema = $document['components']['schemas']['ShopOrderFull']['allOf'][1]['properties'];

        $this->assertArrayHasKey('payment_method', $document['components']['schemas']['ShopOrderShort']['properties']);
        $this->assertArrayHasKey('payment_status', $document['components']['schemas']['ShopOrderShort']['properties']);
        $this->assertArrayHasKey('payment_confirmation_url', $responseSchema);
        $this->assertArrayHasKey('payment_id', $responseSchema);
        $this->assertArrayHasKey('personal_data_consent', $responseSchema);
        $this->assertArrayHasKey('delivery_pickup_point', $responseSchema);
    }

    public function test_shop_product_schema_contains_actual_stock_and_color_fields(): void
    {
        $document = $this->decodeOpenApi();
        $schema = $document['components']['schemas']['ShopProductShort']['properties'];

        $this->assertArrayHasKey('color', $schema);
        $this->assertArrayHasKey('availability_status', $schema);
        $this->assertArrayHasKey('stock_quantity', $schema);
        $this->assertArrayHasKey('is_purchasable', $schema);
    }

    public function test_openapi_does_not_describe_backend_cdek_integration(): void
    {
        $contents = file_get_contents($this->openApiPath());

        $this->assertIsString($contents);
        $this->assertStringNotContainsString('СДЭК API', $contents);
        $this->assertStringNotContainsString('cdek', strtolower($contents));
    }

    private function decodeOpenApi(): array
    {
        $contents = file_get_contents($this->openApiPath());

        $this->assertIsString($contents);

        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }

    private function openApiPath(): string
    {
        return dirname(__DIR__, 2) . '/openapi.json';
    }
}
