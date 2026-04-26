import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/components/product/product_card.dart';
import 'package:shop/models/product_model.dart';
import 'package:shop/constants.dart';
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

  Future<void> _fetchProducts() async {
    try {
      final List<ProductModel> list;
      if (widget.categoryId != null && widget.categoryId! > 0) {
        list = await ApiService.getProductsByCategory(widget.categoryId!, 1);
      } else {
        list = await ApiService.getProducts();
      }
      setState(() {
        products = list;
        isLoading = false;
        error = '';
      });
    } catch (e) {
      setState(() {
        error = 'Error fetching products: ${e.toString()}';
        isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.title ?? 'Products'),
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : error.isNotEmpty
              ? Center(child: Text(error))
              : products.isEmpty
                  ? const Center(child: Text("No products found"))
                  : GridView.builder(
                      padding: const EdgeInsets.all(defaultPadding),
                      gridDelegate:
                          const SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: 2,
                        childAspectRatio: 0.7,
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
                    ),
    );
  }
}
