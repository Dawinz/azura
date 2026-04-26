import 'dart:developer';

import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/components/product/product_card.dart';
import 'package:shop/constants.dart';
import 'package:shop/models/product_model.dart';
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
  ProductModel? _productDetails;
  late Future<List<ProductModel>> _similarFuture;

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
      final viewer =
          (stored != null && stored.id.isNotEmpty) ? stored.id : '0';
      final productDetails =
          await ApiService.getProductDetails(widget.product.slug, viewer);
      setState(() {
        _productDetails = productDetails;
        _similarFuture = ApiService.getSimilarProducts(
          widget.product.slug,
          categoryId: _categoryIdForSimilar(productDetails),
        );
      });
    } catch (e) {
      log(e.toString());
      // Handle error, e.g., show a snackbar or a different UI
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      bottomNavigationBar: ElevatedButton(
        onPressed: () async {
          final messenger = ScaffoldMessenger.of(context);
          final navigator = Navigator.of(context);
          final user = await StorageService.getUser();
          if (!mounted) return;
          if (user == null || user.id.isEmpty) {
            messenger.showSnackBar(
              const SnackBar(
                content: Text('Please log in to purchase this product'),
              ),
            );
            navigator.pushNamed(logInScreenRoute);
            return;
          }
          messenger.showSnackBar(
            const SnackBar(
              content: Text('Checkout flow coming next'),
            ),
          );
        },
        child: const Text("Buy Now"),
      ),
      body: _productDetails == null
          ? const Center(child: CircularProgressIndicator())
          : SafeArea(
              child: CustomScrollView(
                slivers: [
                  SliverAppBar(
                    actions: [
                      IconButton(
                        onPressed: () {},
                        icon: const Icon(Icons.share_outlined),
                      )
                    ],
                  ),
                  SliverToBoxAdapter(
                    child: ProductImages(
                      images: _productDetails!.files!.isNotEmpty
                          ? _productDetails!.files!
                          : [widget.product.image],
                    ),
                  ),
                  ProductInfo(
                    title: _productDetails!.title,
                    brand: _productDetails!.shopName,
                    rating: double.tryParse(_productDetails!.rating) ?? 0,
                    numOfReviews: 0, // No num of reviews in the model
                    isAvailable: (_productDetails!.status == '1' ||
                            _productDetails!.status == 'active') &&
                        _productDetails!.isSold != '1',
                    price: _productDetails!.price,
                    currency: _productDetails!.currency,
                  ),
                  const SliverToBoxAdapter(child: Divider()),
                  SliverPadding(
                    padding: const EdgeInsets.all(defaultPadding),
                    sliver: SliverToBoxAdapter(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            "Description",
                            style: Theme.of(context).textTheme.titleSmall,
                          ),
                          const SizedBox(height: defaultPadding),
                          Text(
                            _productDetails!.description ??
                                'No description available.',
                            style: const TextStyle(height: 1.5),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SliverToBoxAdapter(child: Divider()),
                  SliverToBoxAdapter(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Padding(
                          padding: const EdgeInsets.all(defaultPadding),
                          child: Text(
                            "You may also like",
                            style: Theme.of(context).textTheme.titleSmall,
                          ),
                        ),
                        SizedBox(
                          height: 220,
                          child: FutureBuilder<List<ProductModel>>(
                            future: _similarFuture,
                            builder: (context, snapshot) {
                              if (snapshot.hasData) {
                                return ListView.builder(
                                  scrollDirection: Axis.horizontal,
                                  itemCount: snapshot.data!.length,
                                  itemBuilder: (context, index) => Padding(
                                    padding: EdgeInsets.only(
                                      left: defaultPadding,
                                      right:
                                          index == snapshot.data!.length - 1
                                              ? defaultPadding
                                              : 0,
                                    ),
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
                                );
                              }
                              return const Center(
                                child: CircularProgressIndicator(),
                              );
                            },
                          ),
                        ),
                        const SizedBox(height: defaultPadding),
                      ],
                    ),
                  )
                ],
              ),
            ),
    );
  }
}
