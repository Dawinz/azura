class Product {
  final String id;
  final String? title;
  final String slug;
  final String image;
  final String? penjual;
  final String productType;
  final String listingType;
  final String categoryId;
  final double price;
  final String currency;
  final String? description;
  final String? productCondition;
  final String userId;
  final String status;
  final String isPromoted;
  final String promoteStartDate;
  final String promoteEndDate;
  final String promotePlan;
  final String promoteDay;
  final String visibility;
  final String rating;
  final String externalLink;
  final String filesIncluded;
  final String shippingTime;
  final String isSold;
  final String isDeleted;
  final String isDraft;
  final String createdAt;
  final String userUsername;
  final String shopName;
  final String userRole;
  final String userSlug;
  final String productUrl;

  Product({
    required this.id,
    this.title,
    required this.slug,
    required this.image,
    this.penjual,
    required this.productType,
    required this.listingType,
    required this.categoryId,
    required this.price,
    required this.currency,
    this.description,
    this.productCondition,
    required this.userId,
    required this.status,
    required this.isPromoted,
    required this.promoteStartDate,
    required this.promoteEndDate,
    required this.promotePlan,
    required this.promoteDay,
    required this.visibility,
    required this.rating,
    required this.externalLink,
    required this.filesIncluded,
    required this.shippingTime,
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

  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: json["id"] as String,
      title: json["title"] as String?,
      slug: json["slug"] as String,
      image: json["image"] as String,
      penjual: json["penjual"] as String?,
      productType: json["product_type"] as String,
      listingType: json["listing_type"] as String,
      categoryId: json["category_id"] as String,
      price:
          (json["price"] as num).toDouble(), // Cast to num first then to Double
      currency: json["currency"] as String,
      description: json["description"] as String?,
      productCondition: json["product_condition"] as String?,
      userId: json["user_id"] as String,
      status: json["status"] as String,
      isPromoted: json["is_promoted"] as String,
      promoteStartDate: json["promote_start_date"] as String,
      promoteEndDate: json["promote_end_date"] as String,
      promotePlan: json["promote_plan"] as String,
      promoteDay: json["promote_day"] as String,
      visibility: json["visibility"] as String,
      rating: json["rating"] as String,
      externalLink: json["external_link"] as String,
      filesIncluded: json["files_included"] as String,
      shippingTime: json["shipping_time"] as String,
      isSold: json["is_sold"] as String,
      isDeleted: json["is_deleted"] as String,
      isDraft: json["is_draft"] as String,
      createdAt: json["created_at"] as String,
      userUsername: json["user_username"] as String,
      shopName: json["shop_name"] as String,
      userRole: json["user_role"] as String,
      userSlug: json["user_slug"] as String,
      productUrl: json["product_url"] as String,
    );
  }
}
