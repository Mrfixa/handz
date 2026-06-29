class MartProductModel {
  final String? id;
  final String? name;
  final String? description;
  final double price;
  final String? image;
  final String? category;
  final bool isActive;
  final int stock;
  final double? discountPrice;
  final String? unit;
  final bool isFeatured;
  final bool isPopular;
  final int soldCount;

  MartProductModel({
    this.id,
    this.name,
    this.description,
    this.price = 0,
    this.image,
    this.category,
    this.isActive = true,
    this.stock = 0,
    this.discountPrice,
    this.unit,
    this.isFeatured = false,
    this.isPopular = false,
    this.soldCount = 0,
  });

  /// Sale price when set and lower than base, else base price.
  double get effectivePrice {
    final d = discountPrice;
    return (d != null && d > 0 && d < price) ? d : price;
  }

  /// True when a sale price applies (UI shows a strike-through original).
  bool get onSale => discountPrice != null && discountPrice! > 0 && discountPrice! < price;

  factory MartProductModel.fromJson(Map<String, dynamic> json) {
    bool asBool(dynamic v) => v == true || v == 1 || v == '1';
    return MartProductModel(
      id: json['id']?.toString(),
      name: json['name']?.toString(),
      description: json['description']?.toString(),
      price: double.tryParse(json['price']?.toString() ?? '') ?? 0,
      image: json['image']?.toString(),
      category: json['category']?.toString(),
      isActive: asBool(json['is_active']),
      stock: int.tryParse(json['stock']?.toString() ?? '') ?? 0,
      discountPrice: json['discount_price'] == null ? null : double.tryParse(json['discount_price'].toString()),
      unit: json['unit']?.toString(),
      isFeatured: asBool(json['is_featured']),
      isPopular: asBool(json['is_popular']),
      soldCount: int.tryParse(json['sold_count']?.toString() ?? '') ?? 0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'description': description,
      'price': price,
      'discount_price': discountPrice,
      'unit': unit,
      'image': image,
      'category': category,
      'is_active': isActive,
      'is_featured': isFeatured,
      'is_popular': isPopular,
      'sold_count': soldCount,
      'stock': stock,
    };
  }

  bool get inStock => isActive && stock > 0;
}
