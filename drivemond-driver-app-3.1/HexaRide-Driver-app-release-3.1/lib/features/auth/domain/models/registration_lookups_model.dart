class RegistrationLookupsModel {
  final List<String> identityTypes;
  final List<VehicleCategoryLookup> vehicleCategories;
  final List<VehicleBrandLookup> vehicleBrands;
  final List<String> fuelTypes;

  RegistrationLookupsModel({
    required this.identityTypes,
    required this.vehicleCategories,
    required this.vehicleBrands,
    required this.fuelTypes,
  });

  factory RegistrationLookupsModel.fromJson(Map<String, dynamic> json) {
    return RegistrationLookupsModel(
      identityTypes: (json['identity_types'] as List<dynamic>?)
              ?.map((e) => e.toString())
              .toList() ??
          [],
      vehicleCategories: (json['vehicle_categories'] as List<dynamic>?)
              ?.map((e) => VehicleCategoryLookup.fromJson(e))
              .toList() ??
          [],
      vehicleBrands: (json['vehicle_brands'] as List<dynamic>?)
              ?.map((e) => VehicleBrandLookup.fromJson(e))
              .toList() ??
          [],
      fuelTypes:
          (json['fuel_types'] as List<dynamic>?)?.map((e) => e.toString()).toList() ??
              [],
    );
  }
}

class VehicleCategoryLookup {
  final String id;
  final String name;
  final String? type;
  final String? icon;

  VehicleCategoryLookup({
    required this.id,
    required this.name,
    this.type,
    this.icon,
  });

  factory VehicleCategoryLookup.fromJson(Map<String, dynamic> json) {
    return VehicleCategoryLookup(
      id: json['id']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      type: json['type']?.toString(),
      icon: json['icon']?.toString(),
    );
  }
}

class VehicleBrandLookup {
  final String id;
  final String name;
  final String? logo;
  final String? categoryId;
  final String? categoryName;

  VehicleBrandLookup({
    required this.id,
    required this.name,
    this.logo,
    this.categoryId,
    this.categoryName,
  });

  factory VehicleBrandLookup.fromJson(Map<String, dynamic> json) {
    return VehicleBrandLookup(
      id: json['id']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      logo: json['logo']?.toString(),
      categoryId: json['category_id']?.toString(),
      categoryName: json['category_name']?.toString(),
    );
  }
}

class VehicleModelLookup {
  final String id;
  final String name;
  final String? brandId;
  final int? seatCapacity;
  final double? maximumWeight;
  final int? hatchBagCapacity;
  final String? engine;

  VehicleModelLookup({
    required this.id,
    required this.name,
    this.brandId,
    this.seatCapacity,
    this.maximumWeight,
    this.hatchBagCapacity,
    this.engine,
  });

  factory VehicleModelLookup.fromJson(Map<String, dynamic> json) {
    return VehicleModelLookup(
      id: json['id']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      brandId: json['brand_id']?.toString(),
      seatCapacity: json['seat_capacity'] is int
          ? json['seat_capacity']
          : int.tryParse(json['seat_capacity']?.toString() ?? ''),
      maximumWeight: json['maximum_weight'] is double
          ? json['maximum_weight']
          : double.tryParse(json['maximum_weight']?.toString() ?? ''),
      hatchBagCapacity: json['hatch_bag_capacity'] is int
          ? json['hatch_bag_capacity']
          : int.tryParse(json['hatch_bag_capacity']?.toString() ?? ''),
      engine: json['engine']?.toString(),
    );
  }
}
