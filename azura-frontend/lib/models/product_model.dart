import 'package:shop/core/app_config.dart';

class ProductModel {
  final String id;
  final String title;
  final String slug;
  final String image;
  final List<String>? files;
  final String? penjual;
  final String? provinsi;
  final String? kabupaten;
  final String? kecamatan;
  final String? hape;
  final String? photoProfile;
  final String productType;
  final String listingType;
  final String categoryId;
  final String? subcategoryId;
  final String? thirdCategoryId;
  final double price;
  final double? priceAfetDiscount;
  final int? dicountpercent;
  final String currency;
  final String? description;
  final String? productCondition;
  final String? countryId;
  final String? stateId;
  final String? cityId;
  final String? address;
  final String? zipCode;
  final String userId;
  final String status;
  final String isPromoted;
  final String promoteStartDate;
  final String promoteEndDate;
  final String promotePlan;
  final String promoteDay;
  final String visibility;
  final String rating;
  final String? hit;
  final String externalLink;
  final String filesIncluded;
  final String shippingTime;
  final String? shippingCostType;
  final int? shippingCost;
  final String isSold;
  final String isDeleted;
  final String isDraft;
  final String createdAt;
  final String userUsername;
  final String shopName;
  final String userRole;
  final String userSlug;
  final String productUrl;

  ProductModel({
    required this.id,
    required this.title,
    required this.slug,
    required this.image,
    this.files,
    this.penjual,
    this.provinsi,
    this.kabupaten,
    this.kecamatan,
    this.hape,
    this.photoProfile,
    required this.productType,
    required this.listingType,
    required this.categoryId,
    this.subcategoryId,
    this.thirdCategoryId,
    required this.price,
    this.priceAfetDiscount,
    this.dicountpercent,
    required this.currency,
    this.description,
    this.productCondition,
    this.countryId,
    this.stateId,
    this.cityId,
    this.address,
    this.zipCode,
    required this.userId,
    required this.status,
    required this.isPromoted,
    required this.promoteStartDate,
    required this.promoteEndDate,
    required this.promotePlan,
    required this.promoteDay,
    required this.visibility,
    required this.rating,
    this.hit,
    required this.externalLink,
    required this.filesIncluded,
    required this.shippingTime,
    this.shippingCostType,
    this.shippingCost,
    required this.isSold,
    required this.isDeleted,
    required this.isDraft,
    required this.createdAt,
    required this.userUsername,
    required this.shopName,
    required this.userRole,
    required this.userSlug,
    required this.productUrl,
  });

  static String _getImageUrl(String path) {
    return AppConfig.resolveUploadUrl(path);
  }

  /// Image from API: absolute URL or upload-relative path.
  static String _resolveProductImage(dynamic raw) {
    if (raw == null) return '';
    final s = raw.toString();
    if (s.isEmpty) return '';
    if (s.startsWith('http://') || s.startsWith('https://')) return s;
    return _getImageUrl(s);
  }

  /// Minimal product row from GET /v1/product/list (Railway V1 API).
  factory ProductModel.fromV1Summary(Map<String, dynamic> json) {
    final priceVal = json['price'];
    final double price = priceVal is num
        ? priceVal.toDouble()
        : double.tryParse(priceVal?.toString() ?? '') ?? 0;
    final disc = json['discount_rate'];
    final int? discountPercent = disc is int ? disc : int.tryParse('$disc');

    return ProductModel(
      id: json['id']?.toString() ?? '',
      title: json['title']?.toString() ?? 'No Title',
      slug: json['slug']?.toString() ?? '',
      image: _resolveProductImage(json['image']),
      productType: '',
      listingType: '',
      categoryId: json['category_id']?.toString() ?? '0',
      price: price,
      priceAfetDiscount: null,
      dicountpercent: discountPercent,
      currency: json['currency']?.toString() ?? '',
      userId: json['user_id']?.toString() ?? '0',
      status: '1',
      isPromoted: json['is_promoted']?.toString() ?? '0',
      promoteStartDate: '',
      promoteEndDate: '',
      promotePlan: '',
      promoteDay: '',
      visibility: '1',
      rating: json['rating']?.toString() ?? '0',
      externalLink: '',
      filesIncluded: '',
      shippingTime: '',
      isSold: json['is_sold']?.toString() ?? '0',
      isDeleted: '0',
      isDraft: '0',
      createdAt: json['created_at']?.toString() ?? '',
      userUsername: '',
      shopName: '',
      userRole: '',
      userSlug: '',
      productUrl: '',
    );
  }

  factory ProductModel.fromJson(Map<String, dynamic> json) {
    final List<String> files = [];
    if (json['files'] != null) {
      for (var file in json['files']) {
        files.add(_resolveProductImage(file));
      }
    }
    final priceVal = json['price'];
    final double price = priceVal is num
        ? priceVal.toDouble()
        : double.tryParse(priceVal?.toString() ?? '0') ?? 0;
    return ProductModel(
      id: json['id']?.toString() ?? '',
      title: json['title']?.toString() ?? 'No Title',
      slug: json['slug']?.toString() ?? '',
      image: _resolveProductImage(json['image']),
      files: files,
      penjual: json['penjual'],
      provinsi: json['provinsi'],
      kabupaten: json['kabupaten'],
      kecamatan: json['kecamatan'],
      hape: json['hape'],
      photoProfile: json['photo_profile'],
      productType: json['product_type']?.toString() ?? '',
      listingType: json['listing_type']?.toString() ?? '',
      categoryId: json['category_id']?.toString() ?? '',
      subcategoryId: json['subcategory_id']?.toString(),
      thirdCategoryId: json['third_category_id']?.toString(),
      price: price,
      priceAfetDiscount: json['price_afet_discount'] is num
          ? (json['price_afet_discount'] as num).toDouble()
          : double.tryParse(json['price_afet_discount']?.toString() ?? ''),
      dicountpercent: json['dicountpercent'],
      currency: json['currency']?.toString() ?? '',
      description: json['description']?.toString(),
      productCondition: json['product_condition']?.toString(),
      countryId: json['country_id']?.toString(),
      stateId: json['state_id']?.toString(),
      cityId: json['city_id']?.toString(),
      address: json['address']?.toString(),
      zipCode: json['zip_code']?.toString(),
      userId: json['user_id']?.toString() ?? '',
      status: json['status']?.toString() ?? '',
      isPromoted: json['is_promoted']?.toString() ?? '0',
      promoteStartDate: json['promote_start_date']?.toString() ?? '',
      promoteEndDate: json['promote_end_date']?.toString() ?? '',
      promotePlan: json['promote_plan']?.toString() ?? '',
      promoteDay: json['promote_day']?.toString() ?? '',
      visibility: json['visibility']?.toString() ?? '',
      rating: json['rating']?.toString() ?? '0',
      hit: json['hit']?.toString(),
      externalLink: json['external_link']?.toString() ?? '',
      filesIncluded: json['files_included']?.toString() ?? '',
      shippingTime: json['shipping_time']?.toString() ?? '',
      shippingCostType: json['shipping_cost_type']?.toString(),
      shippingCost: () {
        final sc = json['shipping_cost'];
        if (sc == null) return null;
        if (sc is int) return sc;
        return int.tryParse(sc.toString());
      }(),
      isSold: json['is_sold']?.toString() ?? '0',
      isDeleted: json['is_deleted']?.toString() ?? '0',
      isDraft: json['is_draft']?.toString() ?? '0',
      createdAt: json['created_at']?.toString() ?? '',
      userUsername: json['user_username']?.toString() ?? '',
      shopName: json['shop_name']?.toString() ?? '',
      userRole: json['user_role']?.toString() ?? '',
      userSlug: json['user_slug']?.toString() ?? '',
      productUrl: json['product_url']?.toString() ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'slug': slug,
      'image': image,
      'files': files,
      'penjual': penjual,
      'provinsi': provinsi,
      'kabupaten': kabupaten,
      'kecamatan': kecamatan,
      'hape': hape,
      'photo_profile': photoProfile,
      'product_type': productType,
      'listing_type': listingType,
      'category_id': categoryId,
      'subcategory_id': subcategoryId,
      'third_category_id': thirdCategoryId,
      'price': price,
      'price_afet_discount': priceAfetDiscount,
      'dicountpercent': dicountpercent,
      'currency': currency,
      'description': description,
      'product_condition': productCondition,
      'country_id': countryId,
      'state_id': stateId,
      'city_id': cityId,
      'address': address,
      'zip_code': zipCode,
      'user_id': userId,
      'status': status,
      'is_promoted': isPromoted,
      'promote_start_date': promoteStartDate,
      'promote_end_date': promoteEndDate,
      'promote_plan': promotePlan,
      'promote_day': promoteDay,
      'visibility': visibility,
      'rating': rating,
      'hit': hit,
      'external_link': externalLink,
      'files_included': filesIncluded,
      'shipping_time': shippingTime,
      'shipping_cost_type': shippingCostType,
      'shipping_cost': shippingCost,
      'is_sold': isSold,
      'is_deleted': isDeleted,
      'is_draft': isDraft,
      'created_at': createdAt,
      'user_username': userUsername,
      'shop_name': shopName,
      'user_role': userRole,
      'user_slug': userSlug,
      'product_url': productUrl,
    };
  }
}
