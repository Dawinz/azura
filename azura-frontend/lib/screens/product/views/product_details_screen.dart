import 'dart:developer';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:share_plus/share_plus.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/components/product/product_card.dart';
import 'package:shop/constants.dart';
import 'package:shop/core/app_config.dart';
import 'package:shop/models/product_model.dart';
import 'package:shop/providers/cart_provider.dart';
import 'package:shop/route/route_constants.dart';
import 'package:shop/services/storage_service.dart';

import 'components/product_images.dart';
import 'components/product_info.dart';

class ProductDetailsScreen extends StatefulWidget {
  const ProductDetailsScreen({super.key, required this.product});

  final ProductModel product;

  @override
  State<ProductDetailsScreen> createState() => _ProductDetailsScreenState();
}

class _ProductDetailsScreenState extends State<ProductDetailsScreen> {
  ProductModel? _detail;
  late Future<List<ProductModel>> _similarFuture;
  bool _loadingDetail = true;

  String? _categoryIdForSimilar(ProductModel? detail) {
    final c = detail?.categoryId ?? widget.product.categoryId;
    if (c.isEmpty || c == '0') return null;
    return c;
  }

  @override
  void initState() {
    super.initState();
    _similarFuture = ApiService.getSimilarProducts(
      widget.product.slug,
      categoryId: _categoryIdForSimilar(null),
    );
    _fetchProductDetails();
  }

  Future<void> _fetchProductDetails() async {
    try {
      final stored = await StorageService.getUser();
      final viewer = (stored != null && stored.id.isNotEmpty) ? stored.id : '0';
      final productDetails =
          await ApiService.getProductDetails(widget.product.slug, viewer);
      if (!mounted) return;
      setState(() {
        _detail = productDetails;
        _loadingDetail = false;
        _similarFuture = ApiService.getSimilarProducts(
          widget.product.slug,
          categoryId: _categoryIdForSimilar(productDetails),
        );
      });
    } catch (e) {
      log('Product detail API: $e');
      if (!mounted) return;
      setState(() {
        _detail = widget.product;
        _loadingDetail = false;
      });
    }
  }

  ProductModel get _display => _detail ?? widget.product;

  List<String> get _imageUrls {
    final p = _display;
    if (p.files != null && p.files!.isNotEmpty) {
      return p.files!;
    }
    if (p.image.isNotEmpty) {
      return [p.image];
    }
    return [];
  }

  Future<void> _shareProduct() async {
    final p = _display;
    final link = p.productUrl.isNotEmpty
        ? p.productUrl
        : '${AppConfig.apiBaseUrl}/${p.slug}';
    final text = '${p.title}\n$link';
    await Share.share(text, subject: p.title);
  }

  Future<void> _addToCart(BuildContext context) async {
    final cart = context.read<CartProvider>();
    final ok = cart.addToCart(_display);
    if (!context.mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          ok
              ? 'Added to cart'
              : 'Digital items are not sold in this app. Choose a physical product.',
        ),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  Future<void> _buyNow(BuildContext context) async {
    final messenger = ScaffoldMessenger.of(context);
    final navigator = Navigator.of(context);
    final user = await StorageService.getUser();
    if (!context.mounted) return;
    if (user == null || user.id.isEmpty) {
      messenger.showSnackBar(
        const SnackBar(
          content: Text('Please log in to complete checkout'),
          behavior: SnackBarBehavior.floating,
        ),
      );
      navigator.pushNamed(logInScreenRoute);
      return;
    }
    final cart = context.read<CartProvider>();
    final ok = cart.replaceCartWith(_display, quantity: 1);
    if (!context.mounted) return;
    if (!ok) {
      messenger.showSnackBar(
        const SnackBar(
          content: Text(
            'Digital items are not sold in this app. Choose a physical product.',
          ),
          behavior: SnackBarBehavior.floating,
        ),
      );
      return;
    }
    navigator.pushNamed(checkoutScreenRoute);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Scaffold(
      backgroundColor: colorScheme.surface,
      body: _loadingDetail
          ? const Center(child: CircularProgressIndicator())
          : CustomScrollView(
              slivers: [
                SliverAppBar(
                  pinned: true,
                  backgroundColor: colorScheme.surface,
                  elevation: 0.5,
                  title: Text(
                    _display.title,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: theme.textTheme.titleMedium,
                  ),
                  actions: [
                    IconButton(
                      tooltip: 'Share',
                      onPressed: _shareProduct,
                      icon: const Icon(Icons.share_outlined),
                    ),
                  ],
                ),
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: defaultPadding),
                    child: _imageUrls.isEmpty
                        ? AspectRatio(
                            aspectRatio: 1,
                            child: ClipRRect(
                              borderRadius: BorderRadius.circular(
                                defaultBorderRadious * 2,
                              ),
                              child: ColoredBox(
                                color: Colors.grey.shade200,
                                child: Icon(
                                  Icons.image_not_supported_outlined,
                                  size: 64,
                                  color: Colors.grey.shade500,
                                ),
                              ),
                            ),
                          )
                        : ProductImages(images: _imageUrls),
                  ),
                ),
                ProductInfo(
                  title: _display.title,
                  brand: _display.shopName.isNotEmpty
                      ? _display.shopName
                      : 'Azuramall',
                  rating: double.tryParse(_display.rating) ?? 0,
                  numOfReviews: 0,
                  isAvailable: (_display.status == '1' ||
                          _display.status == 'active') &&
                      _display.isSold != '1',
                  price: _display.price,
                  currency: _display.currency,
                ),
                const SliverToBoxAdapter(child: Divider(height: 1)),
                SliverPadding(
                  padding: const EdgeInsets.all(defaultPadding),
                  sliver: SliverToBoxAdapter(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Description',
                          style: theme.textTheme.titleSmall,
                        ),
                        const SizedBox(height: defaultPadding),
                        Text(
                          _display.description ?? 'No description available.',
                          style: theme.textTheme.bodyLarge?.copyWith(height: 1.5),
                        ),
                      ],
                    ),
                  ),
                ),
                const SliverToBoxAdapter(child: Divider(height: 1)),
                SliverToBoxAdapter(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Padding(
                        padding: const EdgeInsets.fromLTRB(
                          defaultPadding,
                          defaultPadding,
                          defaultPadding,
                          0,
                        ),
                        child: Text(
                          'You may also like',
                          style: theme.textTheme.titleSmall,
                        ),
                      ),
                      SizedBox(
                        height: 220,
                        child: FutureBuilder<List<ProductModel>>(
                          future: _similarFuture,
                          builder: (context, snapshot) {
                            if (snapshot.hasData && snapshot.data!.isNotEmpty) {
                              return ListView.builder(
                                scrollDirection: Axis.horizontal,
                                itemCount: snapshot.data!.length,
                                itemBuilder: (context, index) => Padding(
                                  padding: EdgeInsets.only(
                                    left: defaultPadding,
                                    right: index == snapshot.data!.length - 1
                                        ? defaultPadding
                                        : 0,
                                  ),
                                  child: SizedBox(
                                    width: 168,
                                    child: ProductCard(
                                      product: snapshot.data![index],
                                      press: () {
                                        Navigator.pushNamed(
                                          context,
                                          productDetailsScreenRoute,
                                          arguments: snapshot.data![index],
                                        );
                                      },
                                    ),
                                  ),
                                ),
                              );
                            }
                            if (snapshot.connectionState ==
                                ConnectionState.waiting) {
                              return const Center(
                                child: CircularProgressIndicator(),
                              );
                            }
                            return const SizedBox.shrink();
                          },
                        ),
                      ),
                      const SizedBox(height: 100),
                    ],
                  ),
                ),
              ],
            ),
      bottomNavigationBar: _loadingDetail
          ? null
          : Material(
              elevation: 12,
              color: colorScheme.surface,
              child: SafeArea(
                top: false,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(
                    defaultPadding,
                    12,
                    defaultPadding,
                    12,
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () => _addToCart(context),
                          icon: const Icon(Icons.add_shopping_cart_outlined),
                          label: const Text('Add to cart'),
                          style: OutlinedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 14),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: FilledButton(
                          onPressed: () => _buyNow(context),
                          style: FilledButton.styleFrom(
                            backgroundColor: primaryColor,
                            padding: const EdgeInsets.symmetric(vertical: 14),
                          ),
                          child: const Text('Buy now'),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
    );
  }
}
