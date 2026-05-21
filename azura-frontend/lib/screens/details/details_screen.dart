import 'dart:developer';

import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/components/product/secondary_product_card.dart';
import 'package:shop/constants.dart';
import 'package:shop/models/product_model.dart';
import 'package:shop/screens/product/views/components/product_images.dart';
import 'package:shop/services/storage_service.dart';

class DetailsScreen extends StatefulWidget {
  const DetailsScreen({super.key, required this.product});

  final ProductModel product;

  @override
  State<DetailsScreen> createState() => _DetailsScreenState();
}

class _DetailsScreenState extends State<DetailsScreen> {
  ProductModel? _productDetails;
  late Future<List<ProductModel>> _similarProductsFuture;

  String? _categoryIdForSimilar(ProductModel? detail) {
    final c = detail?.categoryId ?? widget.product.categoryId;
    if (c.isEmpty || c == '0') return null;
    return c;
  }

  @override
  void initState() {
    super.initState();
    _similarProductsFuture = ApiService.getSimilarProducts(
      widget.product.slug,
      categoryId: _categoryIdForSimilar(null),
    );
    _fetchProductDetails();
  }

  @override
  void didUpdateWidget(covariant DetailsScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.product.slug != widget.product.slug) {
      _similarProductsFuture = ApiService.getSimilarProducts(
        widget.product.slug,
        categoryId: _categoryIdForSimilar(null),
      );
      _fetchProductDetails();
    }
  }

  Future<void> _fetchProductDetails() async {
    try {
      final stored = await StorageService.getUser();
      final viewer = (stored != null && stored.id.isNotEmpty) ? stored.id : '0';
      final productDetails =
          await ApiService.getProductDetails(widget.product.slug, viewer);
      setState(() {
        _productDetails = productDetails;
        _similarProductsFuture = ApiService.getSimilarProducts(
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
      bottomNavigationBar: BottomAppBar(
        child: Text("Price: \$${_productDetails?.priceAfetDiscount}"),
      ),
      body: _productDetails == null
          ? const Center(child: CircularProgressIndicator())
          : SafeArea(
              child: CustomScrollView(
                slivers: [
                  const SliverAppBar(),
                  SliverToBoxAdapter(
                    child: ProductImages(
                      images:
                          _productDetails?.files ?? [_productDetails!.image],
                    ),
                  ),
                  SliverPadding(
                    padding: const EdgeInsets.all(defaultPadding),
                    sliver: SliverToBoxAdapter(
                      child: Text(_productDetails?.title ?? ""),
                    ),
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
                  const SliverPadding(
                    padding: EdgeInsets.all(defaultPadding),
                    sliver: SliverToBoxAdapter(
                      child: Text("You may also like"),
                    ),
                  ),
                  SliverToBoxAdapter(
                    child: SizedBox(
                      height: 250,
                      child: FutureBuilder<List<ProductModel>>(
                        future: _similarProductsFuture,
                        builder: (context, snapshot) {
                          if (snapshot.hasError) {
                            return Center(
                              child: Text(snapshot.error.toString()),
                            );
                          }
                          if (snapshot.hasData) {
                            return ListView.builder(
                              scrollDirection: Axis.horizontal,
                              itemCount: snapshot.data!.length,
                              itemBuilder: (context, index) => Padding(
                                padding: EdgeInsets.only(
                                    left: defaultPadding,
                                    right: index == snapshot.data!.length - 1
                                        ? defaultPadding
                                        : 0),
                                child: SecondaryProductCard(
                                  product: snapshot.data![index],
                                  press: () {
                                    Navigator.push(
                                      context,
                                      MaterialPageRoute(
                                        builder: (context) => DetailsScreen(
                                          product: snapshot.data![index],
                                        ),
                                      ),
                                    );
                                  },
                                ),
                              ),
                            );
                          }
                          return const Center(
                              child: CircularProgressIndicator());
                        },
                      ),
                    ),
                  ),
                  const SliverToBoxAdapter(
                      child: SizedBox(height: defaultPadding)),
                ],
              ),
            ),
    );
  }
}
