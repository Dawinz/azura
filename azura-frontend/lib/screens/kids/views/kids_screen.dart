import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/components/product/product_card.dart';
import 'package:shop/constants.dart';
import 'package:shop/models/category_model.dart';
import 'package:shop/models/product_model.dart';
import 'package:shop/route/route_constants.dart';

/// Picks a child-related category when possible, otherwise shows a short message.
class KidsScreen extends StatefulWidget {
  const KidsScreen({super.key});

  @override
  State<KidsScreen> createState() => _KidsScreenState();
}

class _KidsScreenState extends State<KidsScreen> {
  late Future<_KidsData> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<_KidsData> _load() async {
    final cats = await ApiService.getCategories();
    CategoryModel? pick;
    for (final c in cats) {
      final slug = c.subtitle.toLowerCase();
      final name = c.title.toLowerCase();
      if (slug.contains('kid') ||
          slug.contains('child') ||
          slug.contains('baby') ||
          name.contains('kid') ||
          name.contains('child') ||
          name.contains('baby')) {
        pick = c;
        break;
      }
    }
    if (pick == null) {
      return _KidsData(null, null, []);
    }
    final id = pick.id;
    if (id < 1) {
      return _KidsData(pick.title, null, []);
    }
    final products = await ApiService.getProductsByCategory(id, 1);
    return _KidsData(pick.title, id, products);
  }

  Future<void> _reload() async {
    setState(() => _future = _load());
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(title: const Text('Kids')),
      body: RefreshIndicator(
        onRefresh: _reload,
        child: FutureBuilder<_KidsData>(
          future: _future,
          builder: (context, snap) {
            if (snap.connectionState == ConnectionState.waiting) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: const [
                  SizedBox(height: 120),
                  Center(child: CircularProgressIndicator()),
                ],
              );
            }
            if (snap.hasError) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(defaultPadding * 2),
                children: [
                  Text(snap.error.toString(), style: theme.textTheme.bodyLarge),
                ],
              );
            }
            final d = snap.data!;
            if (d.categoryId == null || d.products.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(defaultPadding * 2),
                children: [
                  Text(
                    d.title == null
                        ? 'No kids category was found in the catalog. Browse all categories in Discover.'
                        : 'No products in “${d.title}” right now.',
                    style: theme.textTheme.bodyLarge,
                    textAlign: TextAlign.center,
                  ),
                ],
              );
            }
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
              itemCount: d.products.length,
              itemBuilder: (context, index) {
                final p = d.products[index];
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
            );
          },
        ),
      ),
    );
  }
}

class _KidsData {
  _KidsData(this.title, this.categoryId, this.products);

  final String? title;
  final int? categoryId;
  final List<ProductModel> products;
}
