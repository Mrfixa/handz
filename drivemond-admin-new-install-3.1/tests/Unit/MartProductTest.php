<?php

namespace Tests\Unit;

use Modules\TripManagement\Entities\MartProduct;
use Tests\TestCase;

class MartProductTest extends TestCase
{
    // ========================================================================
    // Model attribute tests
    // ========================================================================

    public function test_fillable_attributes_are_defined(): void
    {
        $product = new MartProduct();
        $fillable = $product->getFillable();
        
        $requiredFields = [
            'name',
            'description',
            'price',
            'image',
            'category',
            'is_active',
            'stock',
            'zone_id',
        ];
        
        foreach ($requiredFields as $field) {
            $this->assertContains($field, $fillable, "Field '$field' should be fillable");
        }
    }

    public function test_price_is_cast_to_decimal(): void
    {
        $product = new MartProduct();
        $casts = $product->getCasts();
        
        $this->assertEquals('decimal:2', $casts['price']);
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $product = new MartProduct();
        $casts = $product->getCasts();
        
        $this->assertEquals('boolean', $casts['is_active']);
    }

    public function test_stock_is_cast_to_integer(): void
    {
        $product = new MartProduct();
        $casts = $product->getCasts();
        
        $this->assertEquals('integer', $casts['stock']);
    }

    public function test_model_uses_soft_deletes(): void
    {
        $product = new MartProduct();
        
        $this->assertContains('Illuminate\Database\Eloquent\SoftDeletes', class_uses($product));
    }

    public function test_model_uses_uuids(): void
    {
        $product = new MartProduct();
        
        $this->assertContains('Illuminate\Database\Eloquent\Concerns\HasUuids', class_uses($product));
    }

    // ========================================================================
    // Relationship tests
    // ========================================================================

    public function test_order_items_relationship_is_defined(): void
    {
        $product = new MartProduct();
        
        $this->assertTrue(method_exists($product, 'orderItems'));
    }

    public function test_can_create_product_instance(): void
    {
        $product = new MartProduct([
            'name' => 'Test Product',
            'price' => 9.99,
            'stock' => 10,
            'is_active' => true,
            'category' => 'Test',
        ]);
        
        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals(9.99, $product->price);
        $this->assertEquals(10, $product->stock);
        $this->assertTrue($product->is_active);
        $this->assertEquals('Test', $product->category);
    }

    // ========================================================================
    // Stock behavior tests
    // ========================================================================

    public function test_stock_defaults_to_zero_when_not_set(): void
    {
        $product = new MartProduct([
            'name' => 'Unstocked Product',
            'price' => 5.00,
        ]);
        
        $this->assertEquals(0, $product->stock);
    }

    public function test_product_can_have_null_zone_id(): void
    {
        $product = new MartProduct([
            'name' => 'Global Product',
            'price' => 5.00,
            'stock' => 10,
            'zone_id' => null,
        ]);
        
        $this->assertNull($product->zone_id);
    }

    public function test_product_can_have_string_category(): void
    {
        $product = new MartProduct([
            'name' => 'Category Test',
            'price' => 5.00,
            'category' => 'beverages',
        ]);
        
        $this->assertIsString($product->category);
        $this->assertEquals('beverages', $product->category);
    }
}
