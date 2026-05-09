import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/components/product/product_card.dart';
import 'package:shop/models/category_model.dart';
import 'package:shop/models/product_model.dart';
import 'package:shop/route/route_constants.dart';
import 'package:shop/screens/discover/views/components/category_card.dart';
import 'package:shop/screens/product/views/product_list_screen.dart';

import '../../../constants.dart';

class DiscoverScreen extends StatefulWidget {
  const DiscoverScreen({super.key});

  @override
  State<DiscoverScreen> createState() => _DiscoverScreenState();
}

class _DiscoverScreenState extends State<DiscoverScreen> {
  late Future<List<CategoryModel>> _categoriesFuture;
  late Future<List<ProductModel>> _catalogFuture;

  @override
  void initState() {
    super.initState();
    _categoriesFuture = ApiService.getCategories();
    _catalogFuture = ApiService.getBrowseCatalog();
  }

  Future<void> _reload() async {
    setState(() {
      _categoriesFuture = ApiService.getCategories();
      _catalogFuture = ApiService.getBrowseCatalog(forceRefresh: true);
    });
    await _categoriesFuture;
    await _catalogFuture;
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: _reload,
      child: CustomScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        slivers: [
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(
                defaultPadding,
                defaultPadding,
                defaultPadding,
                8,
              ),
              child: Text(
                'Categories',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
            ),
          ),
          FutureBuilder<List<CategoryModel>>(
            future: _categoriesFuture,
            builder: (context, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting) {
                return const SliverToBoxAdapter(
                  child: Padding(
                    padding: EdgeInsets.all(48),
                    child: Center(child: CircularProgressIndicator()),
                  ),
                );
              }
              if (snapshot.hasError) {
                return SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.all(defaultPadding * 2),
                    child: Text(
                      'Unable to load categories.',
                      style: Theme.of(context).textTheme.bodyLarge,
                    ),
                  ),
                );
              }
              final categories = snapshot.data ?? [];
              if (categories.isEmpty) {
                return const SliverToBoxAdapter(
                  child: Padding(
                    padding: EdgeInsets.all(defaultPadding * 2),
                    child: Center(child: Text('No categories yet')),
                  ),
                );
              }
              return SliverPadding(
                padding: const EdgeInsets.symmetric(horizontal: defaultPadding),
                sliver: SliverLayoutBuilder(
                  builder: (context, constraints) {
                    return SliverGrid(
                      gridDelegate: SliverGridDelegateWithMaxCrossAxisExtent(
                        maxCrossAxisExtent:
                            constraints.crossAxisExtent > 600 ? 220 : 180,
                        childAspectRatio: 0.72,
                        mainAxisSpacing: defaultPadding,
                        crossAxisSpacing: defaultPadding,
                      ),
                      delegate: SliverChildBuilderDelegate(
                        (context, index) {
                          final c = categories[index];
                          return CategoryCard(
                            imageUrl: c.imageUrl,
                            title: c.title,
                            subtitle: c.subtitle,
                            press: () {
                              Navigator.of(context).push(
                                MaterialPageRoute<void>(
                                  builder: (context) => ProductListScreen(
                                    categoryId: c.id,
                                    title: displayCategoryTitle(c.title),
                                  ),
                                ),
                              );
                            },
                          );
                        },
                        childCount: categories.length,
                      ),
                    );
                  },
                ),
              );
            },
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(
                defaultPadding,
                defaultPadding * 1.5,
                defaultPadding,
                8,
              ),
              child: Text(
                'All products',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
            ),
          ),
          FutureBuilder<List<ProductModel>>(
            future: _catalogFuture,
            builder: (context, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting) {
                return const SliverToBoxAdapter(
                  child: Padding(
                    padding: EdgeInsets.all(24),
                    child: Center(child: CircularProgressIndicator()),
                  ),
                );
              }
              final products = snapshot.data ?? [];
              if (products.isEmpty) {
                return SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.all(defaultPadding * 2),
                    child: Text(
                      snapshot.hasError
                          ? 'Could not load products.'
                          : 'No products available.',
                      textAlign: TextAlign.center,
                    ),
                  ),
                );
              }
              return SliverPadding(
                padding: const EdgeInsets.fromLTRB(
                  defaultPadding,
                  0,
                  defaultPadding,
                  defaultPadding * 3,
                ),
                sliver: SliverLayoutBuilder(
                  builder: (context, constraints) {
                    return SliverGrid(
                      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount:
                            constraints.crossAxisExtent > 600 ? 3 : 2,
                        childAspectRatio: 0.65,
                        mainAxisSpacing: defaultPadding,
                        crossAxisSpacing: defaultPadding,
                      ),
                      delegate: SliverChildBuilderDelegate(
                        (context, index) {
                          final p = products[index];
                          return ProductCard(
                            product: p,
                            press: () {
                              Navigator.pushNamed(
                                context,
                                productDetailsScreenRoute,
                                arguments: p,
                              );
                            },
                          );
                        },
                        childCount: products.length,
                      ),
                    );
                  },
                ),
              );
            },
          ),
        ],
      ),
    );
  }
}
