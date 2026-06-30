<?php

namespace Modules\UserManagement\Http\Controllers\Api\Driver;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\VehicleManagement\Entities\VehicleBrand;
use Modules\VehicleManagement\Entities\VehicleCategory;
use Modules\VehicleManagement\Entities\VehicleModel;
use Modules\VehicleManagement\Service\Interfaces\VehicleCategoryServiceInterface;

/**
 * Driver Registration Lookup Controller
 * Provides selectable fields for driver registration from database/config
 */
class DriverRegistrationLookupController extends Controller
{
    protected VehicleCategoryServiceInterface $vehicleCategoryService;

    public function __construct(VehicleCategoryServiceInterface $vehicleCategoryService)
    {
        $this->vehicleCategoryService = $vehicleCategoryService;
    }

    /**
     * GET /api/driver/registration/lookups
     * Returns all selectable fields for driver registration
     */
    public function index(Request $request): JsonResponse
    {
        // Identity types from business settings or defaults
        $identityTypes = $this->getIdentityTypes();

        // Vehicle categories (car, motorbike, etc.)
        $vehicleCategories = $this->getVehicleCategories();

        // Vehicle brands
        $vehicleBrands = $this->getVehicleBrands();

        // Fuel types from business settings or defaults
        $fuelTypes = $this->getFuelTypes();

        return response()->json([
            'response_code' => 'default_200',
            'message' => 'Registration lookups retrieved successfully',
            'data' => [
                'identity_types' => $identityTypes,
                'vehicle_categories' => $vehicleCategories,
                'vehicle_brands' => $vehicleBrands,
                'fuel_types' => $fuelTypes,
            ],
        ]);
    }

    /**
     * GET /api/driver/registration/vehicle-models
     * Returns vehicle models filtered by brand and/or category
     */
    public function vehicleModels(Request $request): JsonResponse
    {
        $request->validate([
            'brand_id' => 'nullable|string',
            'category_id' => 'nullable|string',
        ]);

        $query = VehicleModel::where('is_active', 1);

        if ($request->has('brand_id') && $request->brand_id) {
            $query->where('brand_id', $request->brand_id);
        }

        // Filter by category through brand relationship if needed
        if ($request->has('category_id') && $request->category_id) {
            $category = VehicleCategory::find($request->category_id);
            if ($category) {
                // Get brands for this category
                $brandIds = VehicleBrand::where('category_id', $category->id)
                    ->where('is_active', 1)
                    ->pluck('id')
                    ->toArray();
                $query->whereIn('brand_id', $brandIds);
            }
        }

        $models = $query->orderBy('name')->get();

        return response()->json([
            'response_code' => 'default_200',
            'message' => 'Vehicle models retrieved successfully',
            'data' => $models->map(function ($model) {
                return [
                    'id' => $model->id,
                    'name' => $model->name,
                    'brand_id' => $model->brand_id,
                    'seat_capacity' => $model->seat_capacity,
                    'maximum_weight' => $model->maximum_weight,
                    'hatch_bag_capacity' => $model->hatch_bag_capacity,
                    'engine' => $model->engine,
                ];
            }),
        ]);
    }

    /**
     * GET /api/driver/registration/vehicle-brands
     * Returns vehicle brands filtered by category
     */
    public function vehicleBrands(Request $request): JsonResponse
    {
        $request->validate([
            'category_id' => 'nullable|string',
        ]);

        $query = VehicleBrand::where('is_active', 1);

        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $brands = $query->orderBy('name')->get();

        return response()->json([
            'response_code' => 'default_200',
            'message' => 'Vehicle brands retrieved successfully',
            'data' => $brands->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'logo' => $brand->logo,
                    'category_id' => $brand->category_id,
                ];
            }),
        ]);
    }

    /**
     * GET /api/driver/registration/vehicle-categories
     * Returns available vehicle categories
     */
    public function vehicleCategories(): JsonResponse
    {
        $categories = $this->getVehicleCategories();

        return response()->json([
            'response_code' => 'default_200',
            'message' => 'Vehicle categories retrieved successfully',
            'data' => $categories,
        ]);
    }

    /**
     * Get identity types from business settings or use defaults
     */
    private function getIdentityTypes(): array
    {
        // Try to get from business settings
        $identityTypesSetting = businessConfig('identity_types');
        if ($identityTypesSetting && is_array($identityTypesSetting->value)) {
            return $identityTypesSetting->value;
        }

        // Default identity types
        return [
            'passport',
            'driving_license',
            'nid',
        ];
    }

    /**
     * Get vehicle categories from database
     */
    private function getVehicleCategories(): array
    {
        $categories = VehicleCategory::where('is_active', 1)
            ->orderBy('name')
            ->get();

        return $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type,
                'icon' => $category->icon,
            ];
        })->toArray();
    }

    /**
     * Get vehicle brands from database
     */
    private function getVehicleBrands(): array
    {
        $brands = VehicleBrand::where('is_active', 1)
            ->with('category')
            ->orderBy('name')
            ->get();

        return $brands->map(function ($brand) {
            return [
                'id' => $brand->id,
                'name' => $brand->name,
                'logo' => $brand->logo,
                'category_id' => $brand->category_id,
                'category_name' => $brand->category?->name,
            ];
        })->toArray();
    }

    /**
     * Get fuel types from business settings or use defaults
     */
    private function getFuelTypes(): array
    {
        // Try to get from business settings
        $fuelTypesSetting = businessConfig('fuel_types');
        if ($fuelTypesSetting && is_array($fuelTypesSetting->value)) {
            return $fuelTypesSetting->value;
        }

        // Default fuel types
        return [
            'petrol',
            'diesel',
            'electric',
            'hybrid',
        ];
    }
}
