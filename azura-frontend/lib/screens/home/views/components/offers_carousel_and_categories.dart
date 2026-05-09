import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/components/Banner/L/banner_l.dart';
import 'package:shop/components/skleton/banner/banner_l_slelton.dart';
import 'package:shop/components/skleton/others/categories_skelton.dart';
import 'package:shop/constants.dart';
import 'package:shop/core/app_config.dart';
import 'package:shop/models/banner_model.dart';
import 'package:shop/models/category_model.dart';
import 'package:shop/route/route_constants.dart';
import 'package:shop/screens/home/views/components/categories.dart'
    hide CategoryModel;
import 'package:url_launcher/url_launcher.dart';

class OffersCarouselAndCategories extends StatefulWidget {
  const OffersCarouselAndCategories({super.key});

  @override
  OffersCarouselAndCategoriesState createState() =>
      OffersCarouselAndCategoriesState();
}

class OffersCarouselAndCategoriesState
    extends State<OffersCarouselAndCategories> {
  static const Set<String> _allowedHosts = {
    'azuramall.shop',
    'www.azuramall.shop',
  };

  late Future<List<dynamic>> _bannersFuture;
  late Future<List<CategoryModel>> _categoriesFuture;

  @override
  void initState() {
    super.initState();
    _bannersFuture = ApiService.getBanners();
    _categoriesFuture = ApiService.getCategories();
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        FutureBuilder<List<dynamic>>(
          future: _bannersFuture,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) {
              return const BannerLSkelton();
            }
            if (snapshot.hasError) {
              return const Center(child: Text('Failed to load banners'));
            }
            final banners = snapshot.data ?? const <dynamic>[];
            if (banners.isEmpty) {
              return const SizedBox.shrink();
            }
            final first = banners.first;
            String image = '';
            String title = '';
            String link = '';
            if (first is Map<String, dynamic>) {
              image = first['image']?.toString() ?? '';
              title = first['title']?.toString() ?? '';
              link = first['link']?.toString() ?? '';
            } else if (first is BannerModel) {
              image = first.image;
            }
            if (image.isEmpty) {
              return const SizedBox.shrink();
            }
            return BannerL(
              image: image,
              press: () async {
                if (link.isEmpty) return;
                final messenger = ScaffoldMessenger.of(context);
                Uri uri;
                if (link.startsWith('http://') || link.startsWith('https://')) {
                  uri = Uri.parse(link);
                } else {
                  final path = link.startsWith('/') ? link : '/$link';
                  uri = Uri.parse('${AppConfig.apiBaseUrl}$path');
                }
                final isWebLink = uri.scheme == 'http' || uri.scheme == 'https';
                if (isWebLink && !_allowedHosts.contains(uri.host)) {
                  messenger.showSnackBar(
                    const SnackBar(
                      content: Text('This link is not available in the app.'),
                    ),
                  );
                  return;
                }
                if (!await canLaunchUrl(uri)) {
                  messenger.showSnackBar(
                    const SnackBar(content: Text('Could not open link')),
                  );
                  return;
                }
                await launchUrl(uri, mode: LaunchMode.externalApplication);
              },
              children: [
                if (title.isNotEmpty)
                  Positioned(
                    left: 16,
                    right: 16,
                    bottom: 16,
                    child: Text(
                      title,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
              ],
            );
          },
        ),
        const SizedBox(height: defaultPadding),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: defaultPadding),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                "Categories",
                style: Theme.of(context).textTheme.titleSmall,
              ),
              TextButton(
                onPressed: () {
                  Navigator.pushNamed(context, discoverScreenRoute);
                },
                child: const Text("See all"),
              ),
            ],
          ),
        ),
        const SizedBox(height: defaultPadding),
        FutureBuilder<List<CategoryModel>>(
          future: _categoriesFuture,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) {
              return const CategoriesSkelton();
            }
            if (snapshot.hasError) {
              return const Center(child: Text('Failed to load categories'));
            }
            final categories = snapshot.data!;
            return Categories(categories: categories);
          },
        ),
      ],
    );
  }
}
