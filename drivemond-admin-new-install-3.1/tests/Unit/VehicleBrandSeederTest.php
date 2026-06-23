<?php

namespace Tests\Unit;

use Modules\VehicleManagement\Entities\VehicleBrand;
use Modules\VehicleManagement\Entities\VehicleModel;
use Tests\TestCase;

class VehicleBrandSeederTest extends TestCase
{
    public function test_vehicle_brand_has_correct_fillable_attributes(): void
    {
        $brand = new VehicleBrand();
        $fillable = $brand->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('is_active', $fillable);
        $this->assertContains('image', $fillable);
        $this->assertContains('description', $fillable);
    }

    public function test_vehicle_model_has_correct_fillable_attributes(): void
    {
        $model = new VehicleModel();
        $fillable = $model->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('brand_id', $fillable);
        $this->assertContains('seat_capacity', $fillable);
        $this->assertContains('engine', $fillable);
        $this->assertContains('is_active', $fillable);
    }

    public function test_brand_has_vehicle_models_relationship(): void
    {
        $brand = new VehicleBrand(['name' => 'Toyota', 'is_active' => 1]);
        
        $this->assertTrue(method_exists($brand, 'vehicleModels'));
    }

    public function test_model_has_brand_relationship(): void
    {
        $model = new VehicleModel(['name' => 'Camry', 'is_active' => 1]);
        
        $this->assertTrue(method_exists($model, 'brand'));
    }

    public function test_model_has_vehicles_relationship(): void
    {
        $model = new VehicleModel(['name' => 'Camry', 'is_active' => 1]);
        
        $this->assertTrue(method_exists($model, 'vehicles'));
    }

    public function test_brand_has_vehicles_relationship(): void
    {
        $brand = new VehicleBrand(['name' => 'Toyota', 'is_active' => 1]);
        
        $this->assertTrue(method_exists($brand, 'vehicles'));
    }

    public function test_brand_is_active_is_boolean(): void
    {
        $brand = new VehicleBrand(['name' => 'Toyota', 'is_active' => 1]);
        
        $casts = $brand->getCasts();
        $this->assertEquals('boolean', $casts['is_active']);
    }

    public function test_model_is_active_is_boolean(): void
    {
        $model = new VehicleModel(['name' => 'Camry', 'is_active' => 1]);
        
        $casts = $model->getCasts();
        $this->assertEquals('boolean', $casts['is_active']);
    }

    public function test_model_seat_capacity_is_integer(): void
    {
        $model = new VehicleModel(['name' => 'Camry', 'is_active' => 1]);
        
        $casts = $model->getCasts();
        $this->assertEquals('integer', $casts['seat_capacity']);
    }

    public function test_brand_uses_soft_deletes(): void
    {
        $traits = class_uses(VehicleBrand::class);
        
        $this->assertContains('Illuminate\Database\Eloquent\SoftDeletes', $traits);
    }

    public function test_model_uses_soft_deletes(): void
    {
        $traits = class_uses(VehicleModel::class);
        
        $this->assertContains('Illuminate\Database\Eloquent\SoftDeletes', $traits);
    }

    public function test_brand_uses_uuid(): void
    {
        $traits = class_uses(VehicleBrand::class);
        
        $this->assertContains('App\Traits\HasUuid', $traits);
    }

    public function test_model_uses_uuid(): void
    {
        $traits = class_uses(VehicleModel::class);
        
        $this->assertContains('App\Traits\HasUuid', $traits);
    }
}
