import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/components/product/product_card.dart';
import 'package:shop/constants.dart';
import 'package:shop/models/product_model.dart';
import 'package:shop/route/route_constants.dart';

class BookmarkScreen extends StatefulWidget {
  const BookmarkScreen({super.key});

  @override
  State<BookmarkScreen> createState() => _BookmarkScreenState();
}

class _BookmarkScreenState extends State<BookmarkScreen> {
  late Future<List<ProductModel>> _future;

  @override
  void initState() {
    super.initState();
    _future = ApiService.getBrowseCatalog();
  }

  Future<void> _reload({bool forceRefresh = false}) async {
    setState(() {
      _future = ApiService.getBrowseCatalog(forceRefresh: forceRefresh);
    });
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: () => _reload(forceRefresh: true),
      child: FutureBuilder<List<ProductModel>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              children: const [
                SizedBox(height: 120),
                Center(child: CircularProgressIndicator()),
              ],
            );
          }
          if (snapshot.hasError) {
            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(defaultPadding * 2),
              children: [
                Text(
                  'Unable to load products. Pull down to retry.',
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.bodyLarge,
                ),
              ],
            );
          }
          final list = snapshot.data ?? [];
          if (list.isEmpty) {
            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(defaultPadding * 2),
              children: [
                Center(
                  child: Text(
                    'No products available right now',
                    style: Theme.of(context).textTheme.bodyLarge,
                  ),
                ),
              ],
            );
          }

          return LayoutBuilder(
            builder: (context, constraints) {
              return GridView.builder(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(defaultPadding),
                gridDelegate: SliverGridDelegateWithMaxCrossAxisExtent(
                  maxCrossAxisExtent:
                      MediaQuery.sizeOf(context).width > 700 ? 240 : 200,
                  mainAxisSpacing: defaultPadding,
                  crossAxisSpacing: defaultPadding,
                  childAspectRatio: 0.66,
                ),
                itemCount: list.length,
                itemBuilder: (context, index) {
                  return ProductCard(
                    product: list[index],
                    press: () {
                      Navigator.pushNamed(
                        context,
                        productDetailsScreenRoute,
                        arguments: list[index],
                      );
                    },
                  );
                },
              );
            },
          );
        },
      ),
    );
  }
}
