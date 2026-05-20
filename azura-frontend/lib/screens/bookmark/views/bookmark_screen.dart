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
  late Future<_WishlistLoad> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<_WishlistLoad> _load() async {
    final user = await StorageService.getUser();
    final uid = int.tryParse(user?.id ?? '') ?? 0;
    if (uid < 1) {
      return const _WishlistLoad.guest();
    }
    try {
      final products = await ApiService.getWishlistProducts(uid);
      return _WishlistLoad.signedIn(products);
    } catch (e) {
      return _WishlistLoad.error(e.toString());
    }
  }

  Future<void> _reload() async {
    setState(() => _future = _load());
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return FutureBuilder<_WishlistLoad>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        }
        final load = snapshot.data ?? const _WishlistLoad.guest();

        if (load.kind == _WishlistKind.guest) {
          return _guestBody(context, theme);
        }
        if (load.kind == _WishlistKind.error) {
          return _errorBody(context, theme, load.message ?? 'Could not load wishlist');
        }

        final list = load.products ?? [];
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

        return RefreshIndicator(
          onRefresh: _reload,
          child: GridView.builder(
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
          ),
        );
      },
    );
  }

  Widget _guestBody(BuildContext context, ThemeData theme) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(defaultPadding * 2),
      children: [
        Icon(Icons.favorite_border, size: 56, color: theme.colorScheme.outline),
        const SizedBox(height: defaultPadding),
        Text(
          'Save items to your wishlist',
          style: theme.textTheme.titleLarge,
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 8),
        Text(
          'You can browse the shop without signing in. Sign in only when you want '
          'to save favorites and sync them to your account.',
          style: theme.textTheme.bodyMedium,
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: defaultPadding * 2),
        Center(
          child: FilledButton(
            onPressed: () => Navigator.pushNamed(context, logInScreenRoute),
            child: const Text('Sign in'),
          ),
        ),
        const SizedBox(height: defaultPadding),
        Center(
          child: TextButton(
            onPressed: () => Navigator.pushNamed(context, discoverScreenRoute),
            child: const Text('Continue browsing'),
          ),
        ),
      ],
    );
  }

  Widget _errorBody(BuildContext context, ThemeData theme, String message) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(defaultPadding * 2),
      children: [
        Text(
          'We could not load your wishlist right now.',
          style: theme.textTheme.titleMedium,
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 8),
        Text(
          message.replaceFirst('Exception: ', ''),
          style: theme.textTheme.bodySmall,
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: defaultPadding),
        Center(
          child: FilledButton(
            onPressed: _reload,
            child: const Text('Try again'),
          ),
        ),
      ],
    );
  }
}

enum _WishlistKind { guest, signedIn, error }

class _WishlistLoad {
  const _WishlistLoad._({
    required this.kind,
    this.products,
    this.message,
  });

  const _WishlistLoad.guest() : this._(kind: _WishlistKind.guest);

  const _WishlistLoad.signedIn(List<ProductModel> products)
      : this._(kind: _WishlistKind.signedIn, products: products);

  const _WishlistLoad.error(String message)
      : this._(kind: _WishlistKind.error, message: message);

  final _WishlistKind kind;
  final List<ProductModel>? products;
  final String? message;
}
