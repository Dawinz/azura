import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/components/product/product_card.dart';
import 'package:shop/constants.dart';
import 'package:shop/models/product_model.dart';
import 'package:shop/models/user_model.dart';
import 'package:shop/route/route_constants.dart';
import 'package:shop/services/storage_service.dart';

/// Seller listings for the signed-in user (uses public `/v1/profile/products` + slug).
class MyShopScreen extends StatefulWidget {
  const MyShopScreen({super.key});

  @override
  State<MyShopScreen> createState() => _MyShopScreenState();
}

class _MyShopScreenState extends State<MyShopScreen> {
  UserModel? _user;
  String? _slug;
  late Future<List<ProductModel>> _future;

  @override
  void initState() {
    super.initState();
    _future = Future.value(<ProductModel>[]);
    _init();
  }

  Future<void> _init() async {
    final u = await StorageService.getUser();
    if (!mounted) return;
    setState(() => _user = u);
    String? slug = u?.slug?.trim();
    if (slug == null || slug.isEmpty) {
      final uid = int.tryParse(u?.id ?? '') ?? 0;
      if (uid >= 1) {
        try {
          final raw = await ApiService.getProfile(uid);
          if (raw is Map<String, dynamic>) {
            slug = raw['slug']?.toString().trim();
          }
        } catch (_) {}
      }
    }
    if (!mounted) return;
    setState(() {
      _slug = slug;
      if (slug != null && slug.isNotEmpty) {
        _future = ApiService.getUserProducts(slug);
      } else {
        _future = Future.value(<ProductModel>[]);
      }
    });
  }

  Future<void> _reload() async {
    final s = _slug;
    if (s == null || s.isEmpty) return;
    setState(() {
      _future = ApiService.getUserProducts(s);
    });
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(title: const Text('My shop')),
      body: RefreshIndicator(
        onRefresh: _reload,
        child: FutureBuilder<List<ProductModel>>(
          future: _future,
          builder: (context, snap) {
            if (_user == null) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: const [
                  SizedBox(height: 120),
                  Center(child: CircularProgressIndicator()),
                ],
              );
            }
            if (_slug == null || _slug!.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(defaultPadding * 2),
                children: [
                  Text(
                    'Your shop slug is not set yet. Complete your profile on the website, then pull to refresh.',
                    style: theme.textTheme.bodyLarge,
                    textAlign: TextAlign.center,
                  ),
                ],
              );
            }
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
                  Text(
                    snap.error.toString().replaceFirst('Exception: ', ''),
                    style: theme.textTheme.bodyLarge,
                  ),
                ],
              );
            }
            final list = snap.data ?? [];
            if (list.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(defaultPadding * 2),
                children: [
                  Text(
                    'No active listings yet. Add products from azuramall.shop.',
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
              itemCount: list.length,
              itemBuilder: (context, index) {
                final p = list[index];
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
