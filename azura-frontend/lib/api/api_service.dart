import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shop/core/app_config.dart';
import 'package:shop/models/category_model.dart';
import 'package:shop/models/order_model.dart';
import 'package:shop/models/product_model.dart';
import 'package:shop/models/user_model.dart';
import 'package:shop/services/catalog_cache_service.dart';

class ApiService {
  static String get _baseUrl => AppConfig.apiEntryUrl;

  /// V1 list: `{ "data": [ ... ] }`; legacy: `{ "data": { "product": [ ... ] } }`.
  static List<dynamic> _productListPayload(dynamic dataField) {
    if (dataField is List) return dataField;
    if (dataField is Map) {
      for (final key in [
        'product',
        'products',
        'items',
        'list',
        'rows',
        'records',
        'data',
      ]) {
        final v = dataField[key];
        if (v is List) return v;
      }
      final single = dataField['product'];
      if (single is Map<String, dynamic>) return <dynamic>[single];
    }
    return const [];
  }

  /// Top-level list, or `data` as list or map-wrapped list (avoids Map vs List cast errors).
  static List<dynamic> _categoryListRows(dynamic decoded) {
    if (decoded is List) return decoded;
    if (decoded is! Map) return const [];
    final data = decoded['data'];
    if (data is List) return data;
    if (data is Map) {
      for (final key in [
        'categories',
        'category',
        'items',
        'list',
        'rows',
        'records',
      ]) {
        final v = data[key];
        if (v is List) return v;
      }
      if (data.isNotEmpty) {
        final vals = data.values.toList();
        if (vals.isNotEmpty &&
            vals.every((e) => e is Map || e is Map<String, dynamic>)) {
          return vals;
        }
      }
    }
    return const [];
  }

  static ProductModel _productModelFromListItem(Map<String, dynamic> m) {
    return ProductModel.fromV1Summary(m);
  }

  /// Drops digital goods from in-app catalog (defense in depth; API also filters).
  static List<ProductModel> _appStoreCatalogOnly(List<ProductModel> products) {
    return products.where((p) => p.isPurchasableInApp).toList();
  }

  static const Duration _httpTimeout = Duration(seconds: 25);

  /// Last successful browse-catalog snapshot (from network or disk). Avoids duplicate requests
  /// when multiple widgets ask for the same list within a short window.
  static List<ProductModel>? _catalogMemory;
  static DateTime? _catalogMemoryAt;
  static Future<List<ProductModel>>? _catalogInFlight;

  /// Skip new network/disk work if the last resolved catalog is younger than this.
  static const Duration _catalogMemoryTtl = Duration(minutes: 3);

  /// Direct `GET /v1/product/list`. Prefer [getBrowseCatalog] for any UI list so calls are
  /// deduplicated, memory-cached for a few minutes, and persisted between launches.
  static Future<List<ProductModel>> getProducts() async {
    try {
      final response = await http
          .get(Uri.parse('$_baseUrl/v1/product/list'))
          .timeout(_httpTimeout);
      if (response.statusCode != 200) {
        return [];
      }
      dynamic decoded;
      try {
        decoded = json.decode(response.body);
      } catch (_) {
        return [];
      }
      if (decoded is Map && decoded['success'] == false) {
        return [];
      }
      final Map<String, dynamic>? root =
          decoded is Map<String, dynamic> ? decoded : null;
      if (root == null) {
        return [];
      }
      dynamic dataField = root['data'];
      if (dataField == null && root.containsKey('products')) {
        dataField = root['products'];
      }
      final rawList = _productListPayload(dataField ?? root);
      final mapped = rawList.map((e) {
        if (e is! Map) {
          throw const FormatException('Invalid product row');
        }
        return _productModelFromListItem(Map<String, dynamic>.from(e));
      }).toList();
      return _appStoreCatalogOnly(mapped);
    } on TimeoutException {
      return [];
    } catch (_) {
      return [];
    }
  }

  /// Product grid for home, discover, bookmark, search, etc.
  ///
  /// Data always originates from the backend (via HTTP or a persisted snapshot of it).
  /// This method **coalesces** concurrent calls, reuses a **short in-memory TTL** so navigating
  /// between screens does not refetch every few seconds, and uses **disk cache** between sessions.
  /// Use [forceRefresh] for pull-to-refresh or when the user explicitly needs the latest list.
  static Future<List<ProductModel>> getBrowseCatalog({
    bool forceRefresh = false,
  }) async {
    if (!forceRefresh &&
        _catalogMemory != null &&
        _catalogMemory!.isNotEmpty &&
        _catalogMemoryAt != null &&
        DateTime.now().difference(_catalogMemoryAt!) < _catalogMemoryTtl) {
      return List<ProductModel>.from(_catalogMemory!);
    }

    if (!forceRefresh && _catalogInFlight != null) {
      return List<ProductModel>.from(await _catalogInFlight!);
    }

    final future = _resolveBrowseCatalog(forceRefresh: forceRefresh);
    if (!forceRefresh) {
      _catalogInFlight = future;
    }
    try {
      return await future;
    } finally {
      if (!forceRefresh && identical(_catalogInFlight, future)) {
        _catalogInFlight = null;
      }
    }
  }

  static Future<List<ProductModel>> _resolveBrowseCatalog({
    required bool forceRefresh,
  }) async {
    if (!forceRefresh) {
      final cached = await CatalogCacheService.load();
      if (cached != null && cached.isNotEmpty) {
        final list = _appStoreCatalogOnly(cached);
        if (list.isNotEmpty) {
          _catalogMemory = list;
          _catalogMemoryAt = DateTime.now();
          if (!await CatalogCacheService.isFresh()) {
            _scheduleBrowseCatalogRefresh();
          }
          return List<ProductModel>.from(list);
        }
      }
    }

    final list = await getProducts();
    if (list.isNotEmpty) {
      await CatalogCacheService.save(list);
    }
    _catalogMemory = list;
    _catalogMemoryAt = DateTime.now();
    return List<ProductModel>.from(list);
  }

  static void _scheduleBrowseCatalogRefresh() {
    Future<void>(() async {
      try {
        final list = await getProducts();
        if (list.isNotEmpty) {
          await CatalogCacheService.save(list);
          _catalogMemory = List<ProductModel>.from(list);
          _catalogMemoryAt = DateTime.now();
        }
      } catch (_) {}
    });
  }

  /// Same catalog as [getBrowseCatalog] (shared cache / coalescing); avoids a second HTTP stack on home.
  static Future<List<ProductModel>> getMostPopularProducts() async {
    return getBrowseCatalog();
  }

  /// Same-category slice when [categoryId] is set (matches storefront category browsing); otherwise general catalog.
  static Future<List<ProductModel>> getSimilarProducts(
    String slug, {
    String? categoryId,
    int limit = 16,
  }) async {
    try {
      List<ProductModel> list;
      final cid = int.tryParse(categoryId ?? '') ?? 0;
      if (cid > 0) {
        list = await getProductsByCategory(cid, 1);
      } else {
        list = await getBrowseCatalog();
      }
      return _appStoreCatalogOnly(list)
          .where((p) => p.slug.isNotEmpty && p.slug != slug)
          .take(limit)
          .toList();
    } catch (e) {
      if (e.toString().contains('502') ||
          e.toString().contains('Bad Gateway')) {
        throw Exception(
            'Backend server is temporarily unavailable. Please try again.');
      }
      rethrow;
    }
  }

  // Auth
  static Future<UserModel> login(
      String email, String password, String deviceId) async {
    final String loginUrl = '$_baseUrl/v1/auth/login';
    try {
      final response = await http.post(
        Uri.parse(loginUrl),
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: {'email': email, 'password': password, 'device_id': deviceId},
      );
      if (response.statusCode == 200) {
        // Check if response is JSON before parsing
        final contentType = response.headers['content-type'] ?? '';
        if (!contentType.contains('application/json')) {
          throw Exception('Invalid response format from server');
        }
        try {
          final Map<String, dynamic> data =
              json.decode(response.body) as Map<String, dynamic>;
          if (data['success'] == false) {
            throw Exception(data['error']?.toString() ?? 'Login failed');
          }
          return UserModel.fromMap(data);
        } catch (e) {
          throw Exception('Failed to parse login response: ${e.toString()}');
        }
      } else {
        // Try to parse error message
        try {
          final errorData = json.decode(response.body);
          final errorMessage =
              errorData['error'] ?? errorData['message'] ?? 'Failed to login';
          throw Exception(errorMessage);
        } catch (e) {
          throw Exception('Failed to login (Status: ${response.statusCode})');
        }
      }
    } catch (e) {
      if (e.toString().contains('502') ||
          e.toString().contains('Bad Gateway')) {
        throw Exception(
            'Backend server is temporarily unavailable. Please try again.');
      }
      rethrow;
    }
  }

  static Future<void> register(String username, String email, String password,
      String phoneNumber) async {
    final String registerUrl = '$_baseUrl/v1/user/register';
    final response = await http.post(
      Uri.parse(registerUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'username': username,
        'email': email,
        'password': password,
        'phone_number': phoneNumber
      }),
    );
    if (response.statusCode != 200) {
      // Try to parse error message from response
      try {
        final errorData = json.decode(response.body);
        final errorMessage =
            errorData['error'] ?? errorData['message'] ?? 'Failed to register';
        throw Exception(errorMessage);
      } catch (e) {
        // If parsing fails, use status code
        throw Exception('Failed to register (Status: ${response.statusCode})');
      }
    }
    // Check for success response
    try {
      final responseData = json.decode(response.body);
      if (responseData['success'] == false) {
        final errorMessage = responseData['error'] ??
            responseData['message'] ??
            'Registration failed';
        throw Exception(errorMessage);
      }
    } catch (e) {
      // Response is not JSON or doesn't have success field, assume success
    }
  }

  /// Permanently deletes the account after verifying email + password (App Store account deletion).
  static Future<void> deleteAccount({
    required String userId,
    required String email,
    required String password,
  }) async {
    final uri = Uri.parse('$_baseUrl/v1/user/delete');
    final response = await http.post(
      uri,
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'user_id': int.tryParse(userId) ?? 0,
        'email': email,
        'password': password,
      }),
    );
    final contentType = response.headers['content-type'] ?? '';
    if (!contentType.contains('application/json')) {
      throw Exception('Could not delete account (invalid server response)');
    }
    final Map<String, dynamic> data =
        json.decode(response.body) as Map<String, dynamic>;
    if (response.statusCode == 200 && data['success'] == true) {
      return;
    }
    throw Exception(data['error']?.toString() ?? 'Could not delete account');
  }

  static Future<void> forgotPassword(String email) async {
    final String forgotPasswordUrl = '$_baseUrl/v1/auth/forgetpass';
    final response = await http.post(
      Uri.parse(forgotPasswordUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({'email': email}),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to request password reset');
    }
  }

  static Future<void> googleSignIn(String idToken, String deviceId) async {
    final String googleSignInUrl = '$_baseUrl/v1/auth/connect/google';
    final response = await http.post(
      Uri.parse(googleSignInUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({'id_token': idToken, 'device_id': deviceId}),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to sign in with Google');
    }
  }

  static Future<void> facebookSignIn(String deviceId) async {
    final String facebookSignInUrl = '$_baseUrl/v1/auth/connect/facebook';
    final response =
        await http.get(Uri.parse('$facebookSignInUrl?device_id=$deviceId'));
    if (response.statusCode != 200) {
      throw Exception('Failed to sign in with Facebook');
    }
  }

  // Banner
  static Future<List<dynamic>> getBanners() async {
    // Banners endpoint might not exist, return empty list to prevent crashes
    try {
      final String getBannersUrl = '$_baseUrl/v1/banner/list';
      final response = await http.get(Uri.parse(getBannersUrl));
      if (response.statusCode == 200) {
        final contentType = response.headers['content-type'] ?? '';
        if (!contentType.contains('application/json')) {
          return [];
        }
        try {
          final dynamic data = json.decode(response.body);
          if (data is List) {
            return data;
          } else if (data is Map &&
              data.containsKey('data') &&
              data['data'] is List) {
            return data['data'];
          }
          return [];
        } catch (e) {
          return [];
        }
      } else {
        return [];
      }
    } catch (e) {
      // Return empty list if banners fail to load
      return [];
    }
  }

  // Category
  static Future<List<CategoryModel>> getCategories() async {
    final String getCategoriesUrl = '$_baseUrl/v1/category/list';
    try {
      final response = await http.get(Uri.parse(getCategoriesUrl));
      if (response.statusCode == 200) {
        // Check if response is JSON before parsing
        final contentType = response.headers['content-type'] ?? '';
        if (!contentType.contains('application/json')) {
          throw Exception('Invalid response format from server');
        }
        try {
          final decoded = json.decode(response.body);
          final rows = _categoryListRows(decoded);
          return rows
              .map((e) => CategoryModel.fromMap(
                    Map<String, dynamic>.from(e as Map),
                  ))
              .toList();
        } catch (e) {
          return [];
        }
      } else {
        return [];
      }
    } catch (e) {
      return [];
    }
  }

  static Future<List<ProductModel>> getProductsByCategory(
      int categoryId, int page) async {
    final String getProductsByCategoryUrl =
        '$_baseUrl/v1/product/list?category_id=$categoryId&page=$page';
    final response = await http
        .get(Uri.parse(getProductsByCategoryUrl))
        .timeout(_httpTimeout);
    if (response.statusCode != 200) {
      throw Exception('Failed to load products for category');
    }
    final dynamic decoded = json.decode(response.body);
    if (decoded is Map && decoded['success'] == false) {
      throw Exception(decoded['error']?.toString() ?? 'Request failed');
    }
    final Map<String, dynamic> responseData =
        decoded is Map<String, dynamic>
            ? decoded
            : throw Exception('Invalid response format from server');
    dynamic dataField = responseData['data'];
    if (dataField == null && responseData.containsKey('products')) {
      dataField = responseData['products'];
    }
    final rawList = _productListPayload(dataField ?? responseData);
    final mapped = rawList.map((e) {
      if (e is! Map) {
        throw const FormatException('Invalid product row');
      }
      return _productModelFromListItem(Map<String, dynamic>.from(e));
    }).toList();
    return _appStoreCatalogOnly(mapped);
  }

  // Messages
  static Future<void> startNewConversation(
      int userId, int receiverId, String subject, String bodyMessage) async {
    final String startNewConversationUrl = '$_baseUrl/v1/messages/addnew';
    final response = await http.post(
      Uri.parse(startNewConversationUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'user_id': userId,
        'receiver_id': receiverId,
        'subject': subject,
        'body_message': bodyMessage
      }),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to start new conversation');
    }
  }

  static Future<void> sendMessage(
      int userId, int receiverId, int idMessage, String bodyMessage) async {
    final String sendMessageUrl = '$_baseUrl/v1/messages/send';
    final response = await http.post(
      Uri.parse(sendMessageUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'user_id': userId,
        'receiver_id': receiverId,
        'id_message': idMessage,
        'body_message': bodyMessage
      }),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to send message');
    }
  }

  static Future<List<dynamic>> listConversations(int userId) async {
    final String listConversationsUrl =
        '$_baseUrl/v1/messages/list?user_id=$userId';
    final response = await http.get(Uri.parse(listConversationsUrl));
    if (response.statusCode == 200) {
      final List<dynamic> data = json.decode(response.body);
      return data;
    } else {
      throw Exception('Failed to list conversations');
    }
  }

  static Future<dynamic> getConversation(int conversationId, int userId) async {
    final String getConversationUrl =
        '$_baseUrl/v1/messages/conversation?conversation_id=$conversationId&user_id=$userId';
    final response = await http.get(Uri.parse(getConversationUrl));
    if (response.statusCode == 200) {
      final dynamic data = json.decode(response.body);
      return data;
    } else {
      throw Exception('Failed to get conversation');
    }
  }

  static Future<int> getUnreadCount(int userId) async {
    final String getUnreadCountUrl =
        '$_baseUrl/v1/messages/unread-count?user_id=$userId';
    final response = await http.get(Uri.parse(getUnreadCountUrl));
    if (response.statusCode == 200) {
      final Map<String, dynamic> data = json.decode(response.body);
      final raw = data['unread_count'];
      if (raw is int) return raw;
      return int.tryParse(raw?.toString() ?? '0') ?? 0;
    } else {
      throw Exception('Failed to get unread count');
    }
  }

  static Future<void> deleteConversation(int idMessage, int userId) async {
    final String deleteConversationUrl = '$_baseUrl/api/v1/messages/delete';
    final response = await http.post(
      Uri.parse(deleteConversationUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({'id_message': idMessage, 'user_id': userId}),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to delete conversation');
    }
  }

  // Profile
  static Future<dynamic> getPublicProfile(String profileSlug) async {
    final String getPublicProfileUrl =
        '$_baseUrl/v1/profile?profile_slug=$profileSlug';
    final response = await http.get(Uri.parse(getPublicProfileUrl));
    if (response.statusCode == 200) {
      final dynamic data = json.decode(response.body);
      return data;
    } else {
      throw Exception('Failed to get public profile');
    }
  }

  static Future<List<ProductModel>> getUserProducts(String slug) async {
    final String getUserProductsUrl =
        '$_baseUrl/v1/profile/products?slug=$slug';
    final response = await http.get(Uri.parse(getUserProductsUrl));
    if (response.statusCode == 200) {
      final Map<String, dynamic> responseData =
          json.decode(response.body) as Map<String, dynamic>;
      final rawList = _productListPayload(responseData['data']);
      return rawList.map((e) {
        if (e is! Map) {
          throw const FormatException('Invalid product row');
        }
        return _productModelFromListItem(Map<String, dynamic>.from(e));
      }).toList();
    } else {
      throw Exception('Failed to get user products');
    }
  }

  static Future<List<dynamic>> getFavorites(int userId) async {
    final String favoritesUrl =
        '$_baseUrl/api/v1/profile/favorites?user_id=$userId';
    final response = await http.get(Uri.parse(favoritesUrl));
    if (response.statusCode == 200) {
      final List<dynamic> data = json.decode(response.body);
      return data;
    } else {
      throw Exception('Failed to get favorites');
    }
  }

  static Future<List<dynamic>> getFollowers(int userId) async {
    final String followersUrl =
        '$_baseUrl/api/v1/profile/followers?user_id=$userId';
    final response = await http.get(Uri.parse(followersUrl));
    if (response.statusCode == 200) {
      final List<dynamic> data = json.decode(response.body);
      return data;
    } else {
      throw Exception('Failed to get followers');
    }
  }

  static Future<List<dynamic>> getFollowing(int userId) async {
    final String followingUrl =
        '$_baseUrl/api/v1/profile/following?user_id=$userId';
    final response = await http.get(Uri.parse(followingUrl));
    if (response.statusCode == 200) {
      final List<dynamic> data = json.decode(response.body);
      return data;
    } else {
      throw Exception('Failed to get following');
    }
  }

  static Future<List<dynamic>> getReviews(int userId) async {
    final String reviewsUrl =
        '$_baseUrl/api/v1/profile/reviews?user_id=$userId';
    final response = await http.get(Uri.parse(reviewsUrl));
    if (response.statusCode == 200) {
      final List<dynamic> data = json.decode(response.body);
      return data;
    } else {
      throw Exception('Failed to get reviews');
    }
  }

  static Future<List<dynamic>> getDrafts(int userId) async {
    final String draftsUrl = '$_baseUrl/api/v1/profile/drafts?user_id=$userId';
    final response = await http.get(Uri.parse(draftsUrl));
    if (response.statusCode == 200) {
      final List<dynamic> data = json.decode(response.body);
      return data;
    } else {
      throw Exception('Failed to get drafts');
    }
  }

  // Settings
  static Future<dynamic> getProfile(int userId) async {
    try {
      final String getProfileUrl =
          '$_baseUrl/v1/setting/profile?user_id=$userId';
      final response = await http.get(Uri.parse(getProfileUrl));
      if (response.statusCode == 200) {
        final contentType = response.headers['content-type'] ?? '';
        if (!contentType.contains('application/json')) {
          throw Exception('Invalid response format from server');
        }
        try {
          final dynamic data = json.decode(response.body);
          return data;
        } catch (e) {
          throw Exception('Failed to parse profile response: ${e.toString()}');
        }
      } else {
        throw Exception(
            'Failed to get profile (Status: ${response.statusCode})');
      }
    } catch (e) {
      if (e.toString().contains('502') ||
          e.toString().contains('Bad Gateway')) {
        throw Exception(
            'Backend server is temporarily unavailable. Please try again.');
      }
      rethrow;
    }
  }

  static Future<void> updateProfile(int userId, String username, String email,
      String slug, int sendEmailNewMessage) async {
    final String updateProfileUrl = '$_baseUrl/api/v1/setting/profile';
    final response = await http.post(
      Uri.parse(updateProfileUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'user_id': userId,
        'username': username,
        'email': email,
        'slug': slug,
        'send_email_new_message': sendEmailNewMessage,
      }),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to update profile');
    }
  }

  static Future<dynamic> getContactInfo(int userId) async {
    final String getContactInfoUrl =
        '$_baseUrl/api/v1/setting/contact?user_id=$userId';
    final response = await http.get(Uri.parse(getContactInfoUrl));
    if (response.statusCode == 200) {
      final dynamic data = json.decode(response.body);
      return data;
    } else {
      throw Exception('Failed to get contact info');
    }
  }

  static Future<void> updateContactInfo(int userId, String phone,
      String address, int countryId, int stateId, int cityId) async {
    final String updateContactInfoUrl = '$_baseUrl/api/v1/setting/contact';
    final response = await http.post(
      Uri.parse(updateContactInfoUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'user_id': userId,
        'phone': phone,
        'address': address,
        'country_id': countryId,
        'state_id': stateId,
        'city_id': cityId,
      }),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to update contact info');
    }
  }

  static Future<dynamic> getShopSettings(int userId) async {
    final String getShopSettingsUrl =
        '$_baseUrl/v1/setting/shop?user_id=$userId';
    final response = await http.get(Uri.parse(getShopSettingsUrl));
    if (response.statusCode == 200) {
      final dynamic data = json.decode(response.body);
      return data;
    } else {
      throw Exception('Failed to get shop settings');
    }
  }

  static Future<void> updateShopSettings(int userId, String shopName,
      String about, int showRssFeeds, int sendEmailWhenItemSold) async {
    final String updateShopSettingsUrl = '$_baseUrl/api/v1/setting/shop';
    final response = await http.post(
      Uri.parse(updateShopSettingsUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'user_id': userId,
        'shop_name': shopName,
        'about': about,
        'show_rss_feeds': showRssFeeds,
        'send_email_when_item_sold': sendEmailWhenItemSold,
      }),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to update shop settings');
    }
  }

  static Future<dynamic> getShippingAddress(int userId) async {
    final String getShippingAddressUrl =
        '$_baseUrl/api/v1/setting/shipping?user_id=$userId';
    final response = await http.get(Uri.parse(getShippingAddressUrl));
    if (response.statusCode == 200) {
      final dynamic data = json.decode(response.body);
      return data;
    } else {
      throw Exception('Failed to get shipping address');
    }
  }

  static Future<void> updateShippingAddress(
    int userId,
    String shippingFirstName,
    String shippingLastName,
    String shippingEmail,
    String shippingPhoneNumber,
    String shippingAddress1,
    int shippingCountryId,
    String shippingState,
    String shippingCity,
    String shippingZipCode,
  ) async {
    final String updateShippingAddressUrl = '$_baseUrl/api/v1/setting/shipping';
    final response = await http.post(
      Uri.parse(updateShippingAddressUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'user_id': userId,
        'shipping_first_name': shippingFirstName,
        'shipping_last_name': shippingLastName,
        'shipping_email': shippingEmail,
        'shipping_phone_number': shippingPhoneNumber,
        'shipping_address_1': shippingAddress1,
        'shipping_country_id': shippingCountryId,
        'shipping_state': shippingState,
        'shipping_city': shippingCity,
        'shipping_zip_code': shippingZipCode,
      }),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to update shipping address');
    }
  }

  static Future<dynamic> getSocialMedia(int userId) async {
    final String getSocialMediaUrl =
        '$_baseUrl/api/v1/setting/sosmed?user_id=$userId';
    final response = await http.get(Uri.parse(getSocialMediaUrl));
    if (response.statusCode == 200) {
      final dynamic data = json.decode(response.body);
      return data;
    } else {
      throw Exception('Failed to get social media');
    }
  }

  static Future<void> updateSocialMedia(
      int userId, String facebookUrl, String instagramUrl) async {
    final String updateSocialMediaUrl = '$_baseUrl/api/v1/setting/sosmed';
    final response = await http.post(
      Uri.parse(updateSocialMediaUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'user_id': userId,
        'facebook_url': facebookUrl,
        'instagram_url': instagramUrl,
      }),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to update social media');
    }
  }

  static Future<void> changePassword(
      int userId, String currentPassword, String newPassword) async {
    final String changePasswordUrl = '$_baseUrl/api/v1/setting/password';
    final response = await http.post(
      Uri.parse(changePasswordUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'user_id': userId,
        'current_password': currentPassword,
        'new_password': newPassword,
      }),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to change password');
    }
  }

  static Future<void> resendActivationEmail(int userId) async {
    final String resendActivationEmailUrl =
        '$_baseUrl/api/v1/setting/resend-activation';
    final response = await http.post(
      Uri.parse(resendActivationEmailUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({'user_id': userId}),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to resend activation email');
    }
  }

  // Sell
  static Future<dynamic> createProductStep1(
      int userId, String title, int categoryId, String description) async {
    final String createProductStep1Url = '$_baseUrl/v1/sell';
    final response = await http.post(
      Uri.parse(createProductStep1Url),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'user_id': userId,
        'title': title,
        'category_id': categoryId,
        'description': description,
      }),
    );
    if (response.statusCode == 200) {
      final dynamic data = json.decode(response.body);
      return data;
    } else {
      throw Exception('Failed to create product (step 1)');
    }
  }

  static Future<void> addProductDetails(
    int productId,
    int price,
    int quantity,
    String satuan,
    String productCondition,
    String shippingCostType,
    String shippingTime,
    int countryId,
    int stateId,
    int cityId,
  ) async {
    final String addProductDetailsUrl = '$_baseUrl/v1/sell/detail';
    final response = await http.post(
      Uri.parse(addProductDetailsUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'product_id': productId,
        'price': price,
        'quantity': quantity,
        'satuan': satuan,
        'product_condition': productCondition,
        'shipping_cost_type': shippingCostType,
        'shipping_time': shippingTime,
        'country_id': countryId,
        'state_id': stateId,
        'city_id': cityId,
      }),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to add product details');
    }
  }

  static Future<void> uploadImages(
      int productId, List<http.MultipartFile> files) async {
    final String uploadImagesUrl = '$_baseUrl/api/v1/sell/images';
    final request = http.MultipartRequest('POST', Uri.parse(uploadImagesUrl));
    request.fields['product_id'] = productId.toString();
    request.files.addAll(files);
    final response = await request.send();
    if (response.statusCode != 200) {
      throw Exception('Failed to upload images');
    }
  }

  static Future<void> deleteImage(int imageId, int productId) async {
    final String deleteImageUrl = '$_baseUrl/api/v1/sell/image/delete';
    final response = await http.post(
      Uri.parse(deleteImageUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({'image_id': imageId, 'product_id': productId}),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to delete image');
    }
  }

  static Future<void> setMainImage(int imageId, int productId) async {
    final String setMainImageUrl = '$_baseUrl/api/v1/sell/image/set-main';
    final response = await http.post(
      Uri.parse(setMainImageUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({'image_id': imageId, 'product_id': productId}),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to set main image');
    }
  }

  static Future<dynamic> getProductForEdit(int userId, int productId) async {
    final String getProductForEditUrl =
        '$_baseUrl/api/v1/sell/edit?user_id=$userId&product_id=$productId';
    final response = await http.get(Uri.parse(getProductForEditUrl));
    if (response.statusCode == 200) {
      final dynamic data = json.decode(response.body);
      return data;
    } else {
      throw Exception('Failed to get product for edit');
    }
  }

  // Product
  static Future<ProductModel> getProductDetails(
      String slug, String userId) async {
    final String getProductDetailsUrl =
        '$_baseUrl/v1/product/detail_get?slug=${Uri.encodeComponent(slug)}&user_id=${Uri.encodeComponent(userId)}';
    final response = await http
        .get(Uri.parse(getProductDetailsUrl))
        .timeout(_httpTimeout);
    if (response.statusCode != 200) {
      try {
        final decoded = json.decode(response.body);
        if (decoded is Map && decoded['error'] != null) {
          throw Exception(decoded['error'].toString());
        }
      } catch (e) {
        if (e is Exception) rethrow;
      }
      throw Exception('Failed to load product details (${response.statusCode})');
    }
    final dynamic decoded = json.decode(response.body);
    if (decoded is Map && decoded['success'] == false) {
      throw Exception(decoded['error']?.toString() ?? 'Product not found');
    }
    if (decoded is! Map<String, dynamic>) {
      throw Exception('Invalid product response');
    }
    return ProductModel.fromJson(decoded);
  }

  static Future<void> toggleFavorite(int userId, int productId) async {
    final String toggleFavoriteUrl = '$_baseUrl/v1/product/favorite';
    final response = await http.post(
      Uri.parse(toggleFavoriteUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({'user_id': userId, 'product_id': productId}),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to toggle favorite');
    }
  }

  static Future<void> markAsSold(int userId, int productId) async {
    final String markAsSoldUrl = '$_baseUrl/v1/product/sold';
    final response = await http.post(
      Uri.parse(markAsSoldUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({'user_id': userId, 'product_id': productId}),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to mark as sold');
    }
  }

  static Future<void> addReview(
      int userId, int productId, int rating, String review) async {
    final String addReviewUrl = '$_baseUrl/v1/product/review';
    final response = await http.post(
      Uri.parse(addReviewUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'user_id': userId,
        'product_id': productId,
        'rating': rating,
        'review': review
      }),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to add review');
    }
  }

  static Future<void> deleteReview(
      int userId, int productId, int reviewId) async {
    final String deleteReviewUrl = '$_baseUrl/v1/product/review/delete';
    final response = await http.post(
      Uri.parse(deleteReviewUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode(
          {'user_id': userId, 'product_id': productId, 'review_id': reviewId}),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to delete review');
    }
  }

  static Future<void> addComment(
      int userId, int productId, String comment) async {
    final String addCommentUrl = '$_baseUrl/api/v1/product/comment';
    final response = await http.post(
      Uri.parse(addCommentUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode(
          {'user_id': userId, 'product_id': productId, 'comment': comment}),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to add comment');
    }
  }

  static Future<void> deleteComment(int commentId, int userId) async {
    final String deleteCommentUrl = '$_baseUrl/api/v1/product/comment/delete';
    final response = await http.post(
      Uri.parse(deleteCommentUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({'comment_id': commentId, 'user_id': userId}),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to delete comment');
    }
  }

  /// GET /v1/wishlist/products — wishlist rows as product summaries.
  static Future<List<ProductModel>> getWishlistProducts(int userId) async {
    final uri = Uri.parse('$_baseUrl/v1/wishlist/products?user_id=$userId');
    final response = await http.get(uri).timeout(_httpTimeout);
    if (response.statusCode != 200) {
      throw Exception('Could not load wishlist');
    }
    final decoded = json.decode(response.body);
    if (decoded is! Map<String, dynamic> || decoded['success'] != true) {
      return [];
    }
    final data = decoded['data'];
    if (data is! List) return [];
    return data
        .whereType<Map>()
        .map((e) => _productModelFromListItem(Map<String, dynamic>.from(e)))
        .toList();
  }

  /// GET /v1/buyer/orders
  static Future<List<OrderModel>> getBuyerOrders(int userId) async {
    final uri = Uri.parse('$_baseUrl/v1/buyer/orders?user_id=$userId&limit=40');
    final response = await http.get(uri).timeout(_httpTimeout);
    if (response.statusCode != 200) {
      throw Exception('Could not load orders');
    }
    final decoded = json.decode(response.body);
    if (decoded is! Map<String, dynamic> || decoded['success'] != true) {
      return [];
    }
    final data = decoded['data'];
    if (data is! List) return [];
    return data
        .whereType<Map>()
        .map((e) => OrderModel.fromBuyerOrdersJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  /// GET /v1/buyer/order
  static Future<Map<String, dynamic>> getBuyerOrderDetail({
    required int userId,
    required String orderNumber,
  }) async {
    final uri = Uri.parse(
      '$_baseUrl/v1/buyer/order?user_id=$userId&order_number=${Uri.encodeComponent(orderNumber)}',
    );
    final response = await http.get(uri).timeout(_httpTimeout);
    if (response.statusCode != 200) {
      throw Exception('Could not load order');
    }
    final decoded = json.decode(response.body);
    if (decoded is! Map<String, dynamic>) {
      throw Exception('Invalid order response');
    }
    if (decoded['success'] != true) {
      throw Exception(decoded['error']?.toString() ?? 'Order not found');
    }
    return decoded;
  }

  /// GET /v1/wallet/summary
  static Future<WalletSummary> getWalletSummary(int userId) async {
    final uri = Uri.parse('$_baseUrl/v1/wallet/summary?user_id=$userId');
    final response = await http.get(uri).timeout(_httpTimeout);
    if (response.statusCode != 200) {
      throw Exception('Could not load wallet');
    }
    final decoded = json.decode(response.body);
    if (decoded is! Map<String, dynamic> || decoded['success'] != true) {
      throw Exception(decoded['error']?.toString() ?? 'Wallet unavailable');
    }
    final hist = decoded['history'];
    final List<WalletHistoryLine> lines = [];
    if (hist is List) {
      for (final e in hist) {
        if (e is! Map) continue;
        final m = Map<String, dynamic>.from(e);
        lines.add(WalletHistoryLine(
          type: m['type']?.toString() ?? '',
          label: m['label']?.toString() ?? '',
          amountCents: (m['amount_cents'] is int)
              ? m['amount_cents'] as int
              : int.tryParse('${m['amount_cents'] ?? 0}') ?? 0,
          currency: m['currency']?.toString() ?? 'TZS',
          createdAt: m['created_at']?.toString() ?? '',
        ));
      }
    }
    final bal = decoded['balance_cents'];
    final balanceCents = bal is int
        ? bal
        : int.tryParse('${bal ?? 0}') ?? 0;
    return WalletSummary(
      balanceCents: balanceCents,
      currency: decoded['currency']?.toString() ?? 'TZS',
      history: lines,
    );
  }

  /// GET /v1/notifications
  static Future<List<NotificationFeedItem>> getNotificationsFeed(
      int userId) async {
    final uri = Uri.parse('$_baseUrl/v1/notifications?user_id=$userId');
    final response = await http.get(uri).timeout(_httpTimeout);
    if (response.statusCode != 200) {
      return [];
    }
    final decoded = json.decode(response.body);
    if (decoded is! Map<String, dynamic> || decoded['success'] != true) {
      return [];
    }
    final data = decoded['data'];
    if (data is! List) return [];
    return data
        .whereType<Map>()
        .map((e) => NotificationFeedItem(
              title: e['title']?.toString() ?? '',
              body: e['body']?.toString() ?? '',
              time: e['time']?.toString() ?? '',
            ))
        .toList();
  }

  /// GET /v1/product/reviews
  static Future<List<ProductReviewItem>> getProductReviews(int productId) async {
    final uri =
        Uri.parse('$_baseUrl/v1/product/reviews?product_id=$productId');
    final response = await http.get(uri).timeout(_httpTimeout);
    if (response.statusCode != 200) {
      return [];
    }
    final decoded = json.decode(response.body);
    if (decoded is! Map<String, dynamic> || decoded['success'] != true) {
      return [];
    }
    final data = decoded['data'];
    if (data is! List) return [];
    return data
        .whereType<Map>()
        .map((e) => ProductReviewItem(
              id: (e['id'] is int) ? e['id'] as int : int.tryParse('${e['id']}') ?? 0,
              rating: (e['rating'] is int)
                  ? e['rating'] as int
                  : int.tryParse('${e['rating'] ?? 0}') ?? 0,
              review: e['review']?.toString() ?? '',
              createdAt: e['created_at']?.toString() ?? '',
              username: e['username']?.toString() ?? '',
            ))
        .toList();
  }

  // Promote
  static Future<List<dynamic>> getPromotionPlans() async {
    final String getPromotionPlansUrl = '$_baseUrl/v1/promote/plan';
    final response = await http.get(Uri.parse(getPromotionPlansUrl));
    if (response.statusCode == 200) {
      final List<dynamic> data = json.decode(response.body);
      return data;
    } else {
      throw Exception('Failed to get promotion plans');
    }
  }

  static Future<dynamic> startPromotionPayment(
    int productId,
    String plan,
    int duration,
    String buyerName,
    String email,
    String phone,
  ) async {
    final String startPromotionPaymentUrl = '$_baseUrl/api/v1/promote/plan';
    final response = await http.post(
      Uri.parse(startPromotionPaymentUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'product_id': productId,
        'plan': plan,
        'duration': duration,
        'buyer_name': buyerName,
        'email': email,
        'phone': phone,
      }),
    );
    if (response.statusCode == 200) {
      final dynamic data = json.decode(response.body);
      return data;
    } else {
      throw Exception('Failed to start promotion payment');
    }
  }

  /// GET /v1/location/countries
  static Future<List<Map<String, dynamic>>> getLocationCountries() async {
    final uri = Uri.parse('$_baseUrl/v1/location/countries');
    final response = await http.get(uri).timeout(_httpTimeout);
    if (response.statusCode != 200) {
      throw Exception('Could not load countries');
    }
    final dynamic decoded = json.decode(response.body);
    if (decoded is! Map || decoded['success'] != true) {
      return [];
    }
    final data = decoded['data'];
    if (data is! List) return [];
    return data
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
  }

  /// GET /v1/location/states?country_id=
  static Future<List<Map<String, dynamic>>> getLocationStates(
      int countryId) async {
    final uri = Uri.parse(
        '$_baseUrl/v1/location/states?country_id=$countryId');
    final response = await http.get(uri).timeout(_httpTimeout);
    if (response.statusCode != 200) {
      throw Exception('Could not load regions');
    }
    final dynamic decoded = json.decode(response.body);
    if (decoded is! Map || decoded['success'] != true) {
      return [];
    }
    final data = decoded['data'];
    if (data is! List) return [];
    return data
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
  }

  /// Starts Selcom hosted checkout. POST /v1/checkout/selcom/init
  /// [shippingAddress] required when the store uses marketplace shipping (physical goods).
  static Future<SelcomCheckoutInitResult> initSelcomCheckout({
    required List<Map<String, dynamic>> lines,
    required String buyerName,
    required String buyerEmail,
    String? buyerPhone,
    String? bearerToken,
    Map<String, dynamic>? shippingAddress,
  }) async {
    final uri = Uri.parse('$_baseUrl/v1/checkout/selcom/init');
    final headers = <String, String>{
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (bearerToken != null && bearerToken.isNotEmpty)
        'Authorization': 'Bearer $bearerToken',
    };
    final body = json.encode({
      'lines': lines,
      'buyer_name': buyerName,
      'buyer_email': buyerEmail,
      if (buyerPhone != null && buyerPhone.isNotEmpty) 'buyer_phone': buyerPhone,
      if (shippingAddress != null && shippingAddress.isNotEmpty)
        'shipping_address': shippingAddress,
    });
    final response = await http.post(uri, headers: headers, body: body).timeout(_httpTimeout);
    dynamic decoded;
    try {
      decoded = json.decode(response.body);
    } catch (_) {
      throw Exception('Invalid response from checkout server');
    }
    if (decoded is! Map) {
      throw Exception('Invalid checkout response');
    }
    final root = Map<String, dynamic>.from(decoded);
    if (root['success'] != true) {
      final err = root['error']?.toString() ?? 'Checkout failed';
      throw Exception(err);
    }
    final url = root['payment_gateway_url']?.toString() ?? '';
    final token = root['order_token']?.toString() ?? '';
    if (url.isEmpty) {
      throw Exception('No payment URL returned');
    }
    return SelcomCheckoutInitResult(
      paymentGatewayUrl: url,
      orderToken: token,
    );
  }
}

/// Response from [ApiService.initSelcomCheckout].
class SelcomCheckoutInitResult {
  SelcomCheckoutInitResult({
    required this.paymentGatewayUrl,
    required this.orderToken,
  });

  final String paymentGatewayUrl;
  final String orderToken;
}

/// GET /v1/wallet/summary
class WalletSummary {
  WalletSummary({
    required this.balanceCents,
    required this.currency,
    required this.history,
  });

  final int balanceCents;
  final String currency;
  final List<WalletHistoryLine> history;
}

class WalletHistoryLine {
  WalletHistoryLine({
    required this.type,
    required this.label,
    required this.amountCents,
    required this.currency,
    required this.createdAt,
  });

  final String type;
  final String label;
  final int amountCents;
  final String currency;
  final String createdAt;
}

class NotificationFeedItem {
  NotificationFeedItem({
    required this.title,
    required this.body,
    required this.time,
  });

  final String title;
  final String body;
  final String time;
}

class ProductReviewItem {
  ProductReviewItem({
    required this.id,
    required this.rating,
    required this.review,
    required this.createdAt,
    required this.username,
  });

  final int id;
  final int rating;
  final String review;
  final String createdAt;
  final String username;
}
