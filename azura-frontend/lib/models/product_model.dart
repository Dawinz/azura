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

  factory ProductModel.fromJson(Map<String, dynamic> json) {
    final List<String> files = [];
    if (json['files'] != null) {
      for (var file in json['files']) {
        files.add(_getImageUrl(file));
      }
    }
    return ProductModel(
      id: json['id'],
      title: json['title'] ?? 'No Title',
      slug: json['slug'],
      image: _getImageUrl(json['image']),
      files: files,
      penjual: json['penjual'],
      provinsi: json['provinsi'],
      kabupaten: json['kabupaten'],
      kecamatan: json['kecamatan'],
      hape: json['hape'],
      photoProfile: json['photo_profile'],
      productType: json['product_type'],
      listingType: json['listing_type'],
      categoryId: json['category_id'],
      subcategoryId: json['subcategory_id'],
      thirdCategoryId: json['third_category_id'],
      price: json['price'].toDouble(),
      priceAfetDiscount: json['price_afet_discount']?.toDouble(),
      dicountpercent: json['dicountpercent'],
      currency: json['currency'],
      description: json['description'],
      productCondition: json['product_condition'],
      countryId: json['country_id'],
      stateId: json['state_id'],
      cityId: json['city_id'],
      address: json['address'],
      zipCode: json['zip_code'],
      userId: json['user_id'],
      status: json['status'],
      isPromoted: json['is_promoted'],
      promoteStartDate: json['promote_start_date'],
      promoteEndDate: json['promote_end_date'],
      promotePlan: json['promote_plan'],
      promoteDay: json['promote_day'],
      visibility: json['visibility'],
      rating: json['rating'],
      hit: json['hit'],
      externalLink: json['external_link'],
      filesIncluded: json['files_included'],
      shippingTime: json['shipping_time'],
      shippingCostType: json['shipping_cost_type'],
      shippingCost: json['shipping_cost'],
      isSold: json['is_sold'],
      isDeleted: json['is_deleted'],
      isDraft: json['is_draft'],
      createdAt: json['created_at'],
      userUsername: json['user_username'],
      shopName: json['shop_name'],
      userRole: json['user_role'],
      userSlug: json['user_slug'],
      productUrl: json['product_url'],
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
