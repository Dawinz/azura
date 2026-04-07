import 'dart:developer';

import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/components/buy_full_ui_kit.dart';
import 'package:shop/components/product/product_card.dart';
import 'package:shop/constants.dart';
import 'package:shop/models/product_model.dart';
import 'package:shop/route/route_constants.dart';

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

  @override
  void initState() {
    super.initState();
    _fetchProductDetails();
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
      bottomNavigationBar: ElevatedButton(
        onPressed: () {},
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
                    rating: double.parse(_productDetails!.rating),
                    numOfReviews: 0, // No num of reviews in the model
                    isAvailable: _productDetails!.status == 'active',
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
                            future:
                                ApiService.getSimilarProducts(widget.product.slug),
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
