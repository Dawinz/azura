import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shop/models/category_model.dart';
import 'package:shop/models/product_model.dart';
import 'package:shop/models/user_model.dart';

class ApiService {
  // Change this to switch between local and production
  // For local development: 'http://localhost/azura-backend'
  // For Android Emulator: 'http://10.0.2.2/azura-backend'
  // For production: 'https://api.azuramall.store'
  // Railway deployment (clean URLs via router.php)
  static const String _baseUrl = 'https://azura-backend-production.up.railway.app';

  static Future<List<ProductModel>> getProducts() async {
    try {
      final response = await http.get(Uri.parse('$_baseUrl/v1/product/list'));
      if (response.statusCode == 200) {
        // Check if response is JSON before parsing
        final contentType = response.headers['content-type'] ?? '';
        if (!contentType.contains('application/json')) {
          throw Exception('Invalid response format from server');
        }
        try {
          final responseData = json.decode(response.body);
          final List<dynamic> productList = responseData['data']['product'] ?? [];
          return productList.map((json) => ProductModel.fromJson(json)).toList();
        } catch (e) {
          throw Exception('Failed to parse products response: ${e.toString()}');
        }
      } else {
        throw Exception('Failed to load products (Status: ${response.statusCode})');
      }
    } catch (e) {
      if (e.toString().contains('502') || e.toString().contains('Bad Gateway')) {
        throw Exception('Backend server is temporarily unavailable. Please try again.');
      }
      rethrow;
    }
  }

  static Future<List<ProductModel>> getMostPopularProducts() async {
    try {
      final response = await http.get(Uri.parse('$_baseUrl/v1/product/list'));
      if (response.statusCode == 200) {
        // Check if response is JSON before parsing
        final contentType = response.headers['content-type'] ?? '';
        if (!contentType.contains('application/json')) {
          throw Exception('Invalid response format from server');
        }
        try {
          final responseData = json.decode(response.body);
          final List<dynamic> productList = responseData['data']['product'] ?? [];
          return productList.map((json) => ProductModel.fromJson(json)).toList();
        } catch (e) {
          throw Exception('Failed to parse products response: ${e.toString()}');
        }
      } else {
        throw Exception('Failed to load products (Status: ${response.statusCode})');
      }
    } catch (e) {
      if (e.toString().contains('502') || e.toString().contains('Bad Gateway')) {
        throw Exception('Backend server is temporarily unavailable. Please try again.');
      }
      rethrow;
    }
  }

  static Future<List<ProductModel>> getSimilarProducts(String slug) async {
    try {
      final response = await http.get(Uri.parse('$_baseUrl/v1/product/list'));
      if (response.statusCode == 200) {
        // Check if response is JSON before parsing
        final contentType = response.headers['content-type'] ?? '';
        if (!contentType.contains('application/json')) {
          throw Exception('Invalid response format from server');
        }
        try {
          final responseData = json.decode(response.body);
          final List<dynamic> productList = responseData['data']['product'] ?? [];
          return productList.map((json) => ProductModel.fromJson(json)).toList();
        } catch (e) {
          throw Exception('Failed to parse products response: ${e.toString()}');
        }
      } else {
        throw Exception('Failed to load products (Status: ${response.statusCode})');
      }
    } catch (e) {
      if (e.toString().contains('502') || e.toString().contains('Bad Gateway')) {
        throw Exception('Backend server is temporarily unavailable. Please try again.');
      }
      rethrow;
    }
  }

  // Auth
  static Future<UserModel> login(
      String email, String password, String deviceId) async {
    const String loginUrl = '$_baseUrl/v1/auth/login';
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
          final Map<String, dynamic> data = json.decode(response.body);
          return UserModel.fromMap(data);
        } catch (e) {
          throw Exception('Failed to parse login response: ${e.toString()}');
        }
      } else {
        // Try to parse error message
        try {
          final errorData = json.decode(response.body);
          final errorMessage = errorData['error'] ?? errorData['message'] ?? 'Failed to login';
          throw Exception(errorMessage);
        } catch (e) {
          throw Exception('Failed to login (Status: ${response.statusCode})');
        }
      }
    } catch (e) {
      if (e.toString().contains('502') || e.toString().contains('Bad Gateway')) {
        throw Exception('Backend server is temporarily unavailable. Please try again.');
      }
      rethrow;
    }
  }

  static Future<void> register(String username, String email, String password,
      String phoneNumber) async {
    const String registerUrl = '$_baseUrl/v1/user/register';
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
        final errorMessage = errorData['error'] ?? errorData['message'] ?? 'Failed to register';
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
        final errorMessage = responseData['error'] ?? responseData['message'] ?? 'Registration failed';
        throw Exception(errorMessage);
      }
    } catch (e) {
      // Response is not JSON or doesn't have success field, assume success
    }
  }

  static Future<void> forgotPassword(String email) async {
    const String forgotPasswordUrl = '$_baseUrl/v1/auth/forgetpass';
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
    const String googleSignInUrl = '$_baseUrl/v1/auth/connect/google';
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
    const String facebookSignInUrl = '$_baseUrl/v1/auth/connect/facebook';
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
      const String getBannersUrl = '$_baseUrl/v1/banner/list';
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
          } else if (data is Map && data.containsKey('data') && data['data'] is List) {
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
    const String getCategoriesUrl = '$_baseUrl/v1/category/list';
    try {
      final response = await http.get(Uri.parse(getCategoriesUrl));
      if (response.statusCode == 200) {
        // Check if response is JSON before parsing
        final contentType = response.headers['content-type'] ?? '';
        if (!contentType.contains('application/json')) {
          throw Exception('Invalid response format from server');
        }
        try {
          final List<dynamic> data = json.decode(response.body);
          return data.map((json) => CategoryModel.fromMap(json)).toList();
        } catch (e) {
          throw Exception('Failed to parse categories response: ${e.toString()}');
        }
      } else {
        throw Exception('Failed to load categories (Status: ${response.statusCode})');
      }
    } catch (e) {
      if (e.toString().contains('502') || e.toString().contains('Bad Gateway')) {
        throw Exception('Backend server is temporarily unavailable. Please try again.');
      }
      rethrow;
    }
  }

  static Future<List<ProductModel>> getProductsByCategory(
      int categoryId, int page) async {
    final String getProductsByCategoryUrl =
        '$_baseUrl/v1/category/products?category_id=$categoryId&page=$page';
    final response = await http.get(Uri.parse(getProductsByCategoryUrl));
    if (response.statusCode == 200) {
      final Map<String, dynamic> responseData = json.decode(response.body);
      final List<dynamic> productList = responseData['data']['product'] ?? [];
      return productList.map((json) => ProductModel.fromJson(json)).toList();
    } else {
      throw Exception('Failed to load products for category');
    }
  }

  // Messages
  static Future<void> startNewConversation(
      int userId, int receiverId, String subject, String bodyMessage) async {
    const String startNewConversationUrl = '$_baseUrl/v1/messages/addnew';
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
    const String sendMessageUrl = '$_baseUrl/v1/messages/send';
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
      return data['unread_count'];
    } else {
      throw Exception('Failed to get unread count');
    }
  }

  static Future<void> deleteConversation(int idMessage, int userId) async {
    const String deleteConversationUrl = '$_baseUrl/api/v1/messages/delete';
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
    final String getUserProductsUrl = '$_baseUrl/v1/profile/products?slug=$slug';
    final response = await http.get(Uri.parse(getUserProductsUrl));
    if (response.statusCode == 200) {
      final Map<String, dynamic> responseData = json.decode(response.body);
      final List<dynamic> productList = responseData['data']['product'] ?? [];
      return productList.map((json) => ProductModel.fromJson(json)).toList();
    } else {
      throw Exception('Failed to get user products');
    }
  }

  static Future<List<dynamic>> getFavorites(int userId) async {
    final String favoritesUrl = '$_baseUrl/api/v1/profile/favorites?user_id=$userId';
    final response = await http.get(Uri.parse(favoritesUrl));
    if (response.statusCode == 200) {
      final List<dynamic> data = json.decode(response.body);
      return data;
    } else {
      throw Exception('Failed to get favorites');
    }
  }

  static Future<List<dynamic>> getFollowers(int userId) async {
    final String followersUrl = '$_baseUrl/api/v1/profile/followers?user_id=$userId';
    final response = await http.get(Uri.parse(followersUrl));
    if (response.statusCode == 200) {
      final List<dynamic> data = json.decode(response.body);
      return data;
    } else {
      throw Exception('Failed to get followers');
    }
  }

  static Future<List<dynamic>> getFollowing(int userId) async {
    final String followingUrl = '$_baseUrl/api/v1/profile/following?user_id=$userId';
    final response = await http.get(Uri.parse(followingUrl));
    if (response.statusCode == 200) {
      final List<dynamic> data = json.decode(response.body);
      return data;
    } else {
      throw Exception('Failed to get following');
    }
  }

  static Future<List<dynamic>> getReviews(int userId) async {
    final String reviewsUrl = '$_baseUrl/api/v1/profile/reviews?user_id=$userId';
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
      final String getProfileUrl = '$_baseUrl/v1/setting/profile?user_id=$userId';
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
        throw Exception('Failed to get profile (Status: ${response.statusCode})');
      }
    } catch (e) {
      if (e.toString().contains('502') || e.toString().contains('Bad Gateway')) {
        throw Exception('Backend server is temporarily unavailable. Please try again.');
      }
      rethrow;
    }
  }

  static Future<void> updateProfile(int userId, String username, String email,
      String slug, int sendEmailNewMessage) async {
    const String updateProfileUrl = '$_baseUrl/api/v1/setting/profile';
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

  static Future<void> updateContactInfo(int userId, String phone, String address,
      int countryId, int stateId, int cityId) async {
    const String updateContactInfoUrl = '$_baseUrl/api/v1/setting/contact';
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

  static Future<void> updateShopSettings(int userId, String shopName, String about,
      int showRssFeeds, int sendEmailWhenItemSold) async {
    const String updateShopSettingsUrl = '$_baseUrl/api/v1/setting/shop';
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
    const String updateShippingAddressUrl = '$_baseUrl/api/v1/setting/shipping';
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
    const String updateSocialMediaUrl = '$_baseUrl/api/v1/setting/sosmed';
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
    const String changePasswordUrl = '$_baseUrl/api/v1/setting/password';
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
    const String resendActivationEmailUrl =
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
    const String createProductStep1Url = '$_baseUrl/v1/sell';
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
    const String addProductDetailsUrl = '$_baseUrl/v1/sell/detail';
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
    const String uploadImagesUrl = '$_baseUrl/api/v1/sell/images';
    final request = http.MultipartRequest('POST', Uri.parse(uploadImagesUrl));
    request.fields['product_id'] = productId.toString();
    request.files.addAll(files);
    final response = await request.send();
    if (response.statusCode != 200) {
      throw Exception('Failed to upload images');
    }
  }

  static Future<void> deleteImage(int imageId, int productId) async {
    const String deleteImageUrl = '$_baseUrl/api/v1/sell/image/delete';
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
    const String setMainImageUrl = '$_baseUrl/api/v1/sell/image/set-main';
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
  static Future<ProductModel> getProductDetails(String slug, String userId) async {
    final String getProductDetailsUrl =
        '$_baseUrl/v1/product/detail_get?slug=$slug&user_id=$userId';
    final response = await http.get(Uri.parse(getProductDetailsUrl));
    if (response.statusCode == 200) {
      final Map<String, dynamic> data = json.decode(response.body);
      return ProductModel.fromJson(data);
    } else {
      throw Exception('Failed to load product details');
    }
  }

  static Future<void> toggleFavorite(int userId, int productId) async {
    const String toggleFavoriteUrl = '$_baseUrl/v1/product/favorite';
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
    const String markAsSoldUrl = '$_baseUrl/v1/product/sold';
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
    const String addReviewUrl = '$_baseUrl/v1/product/review';
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

  static Future<void> deleteReview(int userId, int productId, int reviewId) async {
    const String deleteReviewUrl = '$_baseUrl/v1/product/review/delete';
    final response = await http.post(
      Uri.parse(deleteReviewUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'user_id': userId,
        'product_id': productId,
        'review_id': reviewId
      }),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to delete review');
    }
  }

  static Future<void> addComment(
      int userId, int productId, String comment) async {
    const String addCommentUrl = '$_baseUrl/api/v1/product/comment';
    final response = await http.post(
      Uri.parse(addCommentUrl),
      headers: {'Content-Type': 'application/json'},
      body: json
          .encode({'user_id': userId, 'product_id': productId, 'comment': comment}),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to add comment');
    }
  }

  static Future<void> deleteComment(int commentId, int userId) async {
    const String deleteCommentUrl = '$_baseUrl/api/v1/product/comment/delete';
    final response = await http.post(
      Uri.parse(deleteCommentUrl),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({'comment_id': commentId, 'user_id': userId}),
    );
    if (response.statusCode != 200) {
      throw Exception('Failed to delete comment');
    }
  }

  // Promote
  static Future<List<dynamic>> getPromotionPlans() async {
    const String getPromotionPlansUrl = '$_baseUrl/v1/promote/plan';
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
    const String startPromotionPaymentUrl = '$_baseUrl/api/v1/promote/plan';
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
}
