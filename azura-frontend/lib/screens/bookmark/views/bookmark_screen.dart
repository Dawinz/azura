import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/components/product/product_card.dart';
import 'package:shop/constants.dart';
import 'package:shop/models/product_model.dart';
import 'package:shop/route/route_constants.dart';
import 'package:shop/services/storage_service.dart';

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
    _future = _resolve();
  }

  Future<List<ProductModel>> _resolve() async {
    final user = await StorageService.getUser();
    final uid = int.tryParse(user?.id ?? '') ?? 0;
    if (uid < 1) {
      throw StateError('signed_out');
    }
    return ApiService.getWishlistProducts(uid);
  }

  Future<void> _reload({bool forceRefresh = false}) async {
    setState(() {
      _future = _resolve();
    });
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(title: const Text('Wishlist')),
      body: RefreshIndicator(
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
              final msg = snapshot.error.toString();
              if (msg.contains('signed_out')) {
                return ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.all(defaultPadding * 2),
                  children: [
                    Text(
                      'Sign in to view and manage your wishlist.',
                      textAlign: TextAlign.center,
                      style: theme.textTheme.bodyLarge,
                    ),
                    const SizedBox(height: defaultPadding),
                    Center(
                      child: FilledButton(
                        onPressed: () {
                          Navigator.pushNamed(context, logInScreenRoute);
                        },
                        child: const Text('Sign in'),
                      ),
                    ),
                  ],
                );
              }
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(defaultPadding * 2),
                children: [
                  Text(
                    msg.replaceFirst('Exception: ', ''),
                    textAlign: TextAlign.center,
                    style: theme.textTheme.bodyLarge,
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
                      'Your wishlist is empty. Tap the heart on a product to save it here.',
                      style: theme.textTheme.bodyLarge,
                      textAlign: TextAlign.center,
                    ),
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
        ),
      ),
    );
  }
}
