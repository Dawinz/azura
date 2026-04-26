import 'dart:convert';

class CategoryModel {
  final int id;
  final String title;
  final String subtitle;

  CategoryModel({
    required this.id,
    required this.title,
    required this.subtitle,
  });

  Map<String, dynamic> toMap() {
    return {
      'id': id,
      'title': title,
      'subtitle': subtitle,
    };
  }

  factory CategoryModel.fromMap(Map<String, dynamic> map) {
    final idRaw = map['id'];
    final id = idRaw is int
        ? idRaw
        : int.tryParse(idRaw?.toString() ?? '') ?? 0;
    return CategoryModel(
      id: id,
      title: map['title']?.toString() ?? map['name']?.toString() ?? '',
      subtitle:
          map['subtitle']?.toString() ?? map['slug']?.toString() ?? '',
    );
  }

  String toJson() => json.encode(toMap());

  factory CategoryModel.fromJson(String source) =>
      CategoryModel.fromMap(json.decode(source));
}
