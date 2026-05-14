import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';
import 'package:shop/models/product_model.dart';

/// Persists the product catalog so cold starts and short navigation loops avoid redundant HTTP.
///
/// [ApiService.getBrowseCatalog] also keeps an in-memory copy with a short TTL so
/// `FutureBuilder` rebuilds and tab switches do not trigger new requests every few seconds.
class CatalogCacheService {
  static const _jsonKey = 'catalog_products_json_v1';
  static const _atMsKey = 'catalog_products_cached_at_ms';

  /// Disk snapshot age before we prefer a network refresh (background when UI shows cached data).
  static const Duration maxAge = Duration(hours: 6);

  static Future<void> save(List<ProductModel> products) async {
    if (products.isEmpty) return;
    final prefs = await SharedPreferences.getInstance();
    final encoded =
        json.encode(products.map((e) => e.toJson()).toList(growable: false));
    await prefs.setString(_jsonKey, encoded);
    await prefs.setInt(_atMsKey, DateTime.now().millisecondsSinceEpoch);
  }

  static Future<List<ProductModel>?> load() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_jsonKey);
    if (raw == null || raw.isEmpty) return null;
    try {
      final list = json.decode(raw) as List<dynamic>;
      return list
          .map((e) => ProductModel.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList();
    } catch (_) {
      return null;
    }
  }

  static Future<bool> isFresh() async {
    final prefs = await SharedPreferences.getInstance();
    final ms = prefs.getInt(_atMsKey);
    if (ms == null) return false;
    final at = DateTime.fromMillisecondsSinceEpoch(ms);
    return DateTime.now().difference(at) < maxAge;
  }

  static Future<void> clear() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_jsonKey);
    await prefs.remove(_atMsKey);
  }
}
