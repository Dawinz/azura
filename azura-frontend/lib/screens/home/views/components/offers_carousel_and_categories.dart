import 'package:flutter/material.dart';
import 'package:shop/components/Banner/L/banner_l.dart';
import 'package:shop/components/skleton/banner/banner_l_slelton.dart';
import 'package:shop/constants.dart';
import 'package:shop/models/banner_model.dart';
import 'package:shop/models/category_model.dart';
import 'package:shop/route/route_constants.dart';
import 'package:shop/screens/home/views/components/categories.dart';
import 'package:shop/api/api_service.dart';

class OffersCarouselAndCategories extends StatefulWidget {
  const OffersCarouselAndCategories({super.key});

  @override
  _OffersCarouselAndCategoriesState createState() => _OffersCarouselAndCategoriesState();
}

class _OffersCarouselAndCategoriesState extends State<OffersCarouselAndCategories> {
  late Future<List<BannerModel>> _bannersFuture;
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
        FutureBuilder<List<BannerModel>>(
          future: _bannersFuture,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) {
              return const BannerLSkelton();
            }
            if (snapshot.hasError) {
              return const Center(child: Text('Failed to load banners'));
            }
            final banners = snapshot.data!;
            return BannerL(banners: banners);
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
