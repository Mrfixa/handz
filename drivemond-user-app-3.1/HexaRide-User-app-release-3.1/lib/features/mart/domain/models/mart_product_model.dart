class MartProductModel {
  final String? id;
  final String? name;
  final String? description;
  final double price;
  final String? image;
  final String? category;
  final bool isActive;
  final int stock;

  MartProductModel({
    this.id,
    this.name,
    this.description,
    this.price = 0,
    this.image,
    this.category,
    this.isActive = true,
    this.stock = 0,
  });

  factory MartProductModel.fromJson(Map<String, dynamic> json) {
    return MartProductModel(
      id: json['id']?.toString(),
      name: json['name']?.toString(),
      description: json['description']?.toString(),
      price: double.tryParse(json['price']?.toString() ?? '') ?? 0,
      image: json['image']?.toString(),
      category: json['category']?.toString(),
      isActive: json['is_active'] == true || json['is_active'] == 1 || json['is_active'] == '1',
      stock: int.tryParse(json['stock']?.toString() ?? '') ?? 0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'description': description,
      'price': price,
      'image': image,
      'category': category,
      'is_active': isActive,
      'stock': stock,
    };
  }

  bool get inStock => isActive && stock > 0;
}
