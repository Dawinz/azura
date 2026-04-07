class ShopSettings {
  final String shopName;
  final String about;
  final int showRssFeeds;
  final int sendEmailWhenItemSold;

  ShopSettings({
    required this.shopName,
    required this.about,
    required this.showRssFeeds,
    required this.sendEmailWhenItemSold,
  });

  factory ShopSettings.fromJson(Map<String, dynamic> json) {
    return ShopSettings(
      shopName: json['shop_name'],
      about: json['about'],
      showRssFeeds: json['show_rss_feeds'],
      sendEmailWhenItemSold: json['send_email_when_item_sold'],
    );
  }
}
