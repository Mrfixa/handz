class MartCategoryModel {
  final String? id;
  final String? name;
  final String? slug;
  final String? image;

  MartCategoryModel({this.id, this.name, this.slug, this.image});

  factory MartCategoryModel.fromJson(Map<String, dynamic> json) {
    return MartCategoryModel(
      id: json['id']?.toString(),
      name: json['name']?.toString(),
      slug: json['slug']?.toString(),
      image: json['image']?.toString(),
    );
  }
}
