import 'dart:convert';

import 'package:shop/core/app_config.dart';

class CategoryModel {
  final int id;
  final String title;
  final String subtitle;
  /// Absolute image URL from API (`image` field), or null if none.
  final String? imageUrl;

  CategoryModel({
    required this.id,
    required this.title,
    required this.subtitle,
    this.imageUrl,
  });

  Map<String, dynamic> toMap() {
    return {
      'id': id,
      'title': title,
      'subtitle': subtitle,
      'imageUrl': imageUrl,
    };
  }

  factory CategoryModel.fromMap(Map<String, dynamic> map) {
    final idRaw = map['id'];
    final id =
        idRaw is int ? idRaw : int.tryParse(idRaw?.toString() ?? '') ?? 0;
    final rawImage = map['image']?.toString();
    String? imageUrl;
    if (rawImage != null && rawImage.trim().isNotEmpty) {
      final s = rawImage.trim();
      imageUrl =
          s.startsWith('http://') || s.startsWith('https://') ? s : AppConfig.resolveUploadUrl(s);
    }
    return CategoryModel(
      id: id,
      title: map['title']?.toString() ?? map['name']?.toString() ?? '',
      subtitle: map['subtitle']?.toString() ?? map['slug']?.toString() ?? '',
      imageUrl: imageUrl,
    );
  }

  String toJson() => json.encode(toMap());

  factory CategoryModel.fromJson(String source) =>
      CategoryModel.fromMap(json.decode(source));
}
