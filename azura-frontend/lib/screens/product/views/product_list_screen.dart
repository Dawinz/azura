import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/components/product/product_card.dart';
import 'package:shop/constants.dart';
import 'package:shop/models/product_model.dart';
import 'package:shop/route/route_constants.dart';

class ProductListScreen extends StatefulWidget {
  const ProductListScreen({super.key, this.categoryId, this.title});

  /// When set, loads `/v1/product/list?category_id=` like the website category pages.
  final int? categoryId;
  final String? title;

  static String routeName = "/products";

  @override
  State<ProductListScreen> createState() => _ProductListScreenState();
}

class _ProductListScreenState extends State<ProductListScreen> {
  List<ProductModel> products = [];
  bool isLoading = true;
  String error = '';

  @override
  void initState() {
    super.initState();
    _fetchProducts();
  }

  Future<void> _fetchProducts({bool forceRefresh = false}) async {
    setState(() {
      isLoading = true;
      error = '';
    });
    try {
      final List<ProductModel> list;
      if (widget.categoryId != null && widget.categoryId! > 0) {
        list = await ApiService.getProductsByCategory(widget.categoryId!, 1);
      } else {
        list = await ApiService.getBrowseCatalog(forceRefresh: forceRefresh);
      }
      if (!mounted) return;
      setState(() {
        products = list;
        isLoading = false;
        error = '';
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        error = e.toString().replaceFirst('Exception: ', '');
        isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final bg = theme.colorScheme.surface;

    return Scaffold(
      backgroundColor: bg,
      appBar: AppBar(
        title: Text(widget.title ?? 'Products'),
        backgroundColor: bg,
        surfaceTintColor: Colors.transparent,
      ),
      body: RefreshIndicator(
        onRefresh: _fetchProducts,
        child: isLoading
            ? ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: const [
                  SizedBox(height: 120),
                  Center(child: CircularProgressIndicator()),
                ],
              )
            : error.isNotEmpty
                ? ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(defaultPadding * 2),
                    children: [
                      Icon(Icons.cloud_off_outlined,
                          size: 56, color: theme.colorScheme.error),
                      const SizedBox(height: defaultPadding),
                      Text(
                        'Could not load products',
                        style: theme.textTheme.titleMedium,
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 8),
                      Text(
                        error,
                        style: theme.textTheme.bodyMedium?.copyWith(
                          color: theme.colorScheme.onSurfaceVariant,
                        ),
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: defaultPadding * 2),
                      Center(
                        child: FilledButton.icon(
                          onPressed: () => _fetchProducts(forceRefresh: true),
                          icon: const Icon(Icons.refresh),
                          label: const Text('Try again'),
                        ),
                      ),
                    ],
                  )
                : products.isEmpty
                    ? ListView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        padding: const EdgeInsets.all(defaultPadding * 2),
                        children: [
                          Icon(Icons.inventory_2_outlined,
                              size: 56, color: theme.hintColor),
                          const SizedBox(height: defaultPadding),
                          Text(
                            'No products found',
                            style: theme.textTheme.titleMedium,
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: 8),
                          Text(
                            'Pull down to refresh or check back later.',
                            style: theme.textTheme.bodyMedium?.copyWith(
                              color: theme.colorScheme.onSurfaceVariant,
                            ),
                            textAlign: TextAlign.center,
                          ),
                        ],
                      )
                    : LayoutBuilder(
                        builder: (context, constraints) {
                          return GridView.builder(
                            physics: const AlwaysScrollableScrollPhysics(),
                            padding: const EdgeInsets.all(defaultPadding),
                            gridDelegate:
                                SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount:
                                  constraints.maxWidth > 600 ? 3 : 2,
                              childAspectRatio: 0.68,
                              mainAxisSpacing: defaultPadding,
                              crossAxisSpacing: defaultPadding,
                            ),
                            itemCount: products.length,
                            itemBuilder: (context, index) {
                              return ProductCard(
                                product: products[index],
                                press: () {
                                  Navigator.pushNamed(
                                    context,
                                    productDetailsScreenRoute,
                                    arguments: products[index],
                                  );
                                },
                              );
                            },
                          );
                        },
                      ),
      ),
    );
  }
}
