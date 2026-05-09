import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/components/product/product_card.dart';
import 'package:shop/constants.dart';
import 'package:shop/models/product_model.dart';
import 'package:shop/route/route_constants.dart';
import 'package:shop/screens/product/views/product_list_screen.dart';

import 'components/offers_carousel_and_categories.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  late Future<List<ProductModel>> _productsFuture;

  @override
  void initState() {
    super.initState();
    _productsFuture = ApiService.getBrowseCatalog();
  }

  Future<void> _refresh() async {
    setState(() {
      _productsFuture = ApiService.getBrowseCatalog(forceRefresh: true);
    });
    await _productsFuture;
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 960),
            child: RefreshIndicator(
              onRefresh: _refresh,
              child: CustomScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                slivers: [
                  const SliverToBoxAdapter(child: OffersCarouselAndCategories()),
                  const SliverToBoxAdapter(
                      child: SizedBox(height: defaultPadding)),
                  SliverToBoxAdapter(
                    child: Padding(
                      padding: const EdgeInsets.symmetric(
                          horizontal: defaultPadding),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'Featured products',
                            style: theme.textTheme.titleSmall,
                          ),
                          TextButton(
                            onPressed: () {
                              Navigator.of(context).push(
                                MaterialPageRoute<void>(
                                  builder: (context) => const ProductListScreen(
                                    title: 'All products',
                                  ),
                                ),
                              );
                            },
                            child: const Text('See All'),
                          ),
                        ],
                      ),
                    ),
                  ),
                  FutureBuilder<List<ProductModel>>(
                    future: _productsFuture,
                    builder: (context, snapshot) {
                      if (snapshot.connectionState == ConnectionState.waiting) {
                        return const SliverFillRemaining(
                          hasScrollBody: false,
                          child: Center(child: CircularProgressIndicator()),
                        );
                      }

                      final products = snapshot.data ?? const <ProductModel>[];
                      if (products.isEmpty) {
                        return SliverToBoxAdapter(
                          child: Padding(
                            padding: const EdgeInsets.all(defaultPadding * 2),
                            child: Column(
                              children: [
                                Icon(Icons.shopping_bag_outlined,
                                    size: 48, color: theme.hintColor),
                                const SizedBox(height: defaultPadding),
                                Text(
                                  'No products loaded',
                                  style: theme.textTheme.titleMedium,
                                  textAlign: TextAlign.center,
                                ),
                                const SizedBox(height: 8),
                                Text(
                                  'Pull down to refresh. If this persists, '
                                  'check your internet connection or try again later.',
                                  style: theme.textTheme.bodyMedium?.copyWith(
                                    color: theme.colorScheme.onSurfaceVariant,
                                  ),
                                  textAlign: TextAlign.center,
                                ),
                              ],
                            ),
                          ),
                        );
                      }

                      return SliverPadding(
                        padding: const EdgeInsets.all(defaultPadding),
                        sliver: SliverGrid(
                          gridDelegate:
                              const SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: 2,
                            childAspectRatio: 0.65,
                            crossAxisSpacing: defaultPadding,
                            mainAxisSpacing: defaultPadding,
                          ),
                          delegate: SliverChildBuilderDelegate(
                            (context, index) {
                              final product = products[index];
                              return ProductCard(
                                product: product,
                                press: () {
                                  Navigator.pushNamed(
                                    context,
                                    productDetailsScreenRoute,
                                    arguments: product,
                                  );
                                },
                              );
                            },
                            childCount: products.length,
                          ),
                        ),
                      );
                    },
                  )
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
