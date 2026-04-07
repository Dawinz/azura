
import 'dart:developer';

import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/components/product/secondary_product_card.dart';
import 'package:shop/constants.dart';
import 'package:shop/models/product_model.dart';
import 'package:shop/screens/product/views/components/product_images.dart';

class DetailsScreen extends StatefulWidget {
  const DetailsScreen({super.key, required this.product});

  final ProductModel product;

  @override
  State<DetailsScreen> createState() => _DetailsScreenState();
}

class _DetailsScreenState extends State<DetailsScreen> {
  ProductModel? _productDetails;
  late Future<List<ProductModel>> _similarProductsFuture;

  @override
  void initState() {
    super.initState();
    // Initial fetch
    _fetchProductDetails();
    _similarProductsFuture = ApiService.getSimilarProducts(widget.product.slug);
  }

  @override
  void didUpdateWidget(covariant DetailsScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    // Check if the product slug has changed
    if (oldWidget.product.slug != widget.product.slug) {
      // Fetch new product details
      _fetchProductDetails();
      _similarProductsFuture =
          ApiService.getSimilarProducts(widget.product.slug);
    }
  }

  Future<void> _fetchProductDetails() async {
    try {
      final productDetails =
          await ApiService.getProductDetails(widget.product.slug, "2");
      setState(() {
        _productDetails = productDetails;
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
                      images: _productDetails?.files ?? [_productDetails!.image],
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
