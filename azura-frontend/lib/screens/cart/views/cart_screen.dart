import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shop/constants.dart';
import 'package:shop/core/market_format.dart';
import 'package:shop/providers/cart_provider.dart';
import 'package:shop/route/route_constants.dart';

class CartScreen extends StatelessWidget {
  const CartScreen({super.key});

  static String routeName = "/cart";

  void _openProduct(BuildContext context, CartLine line) {
    Navigator.pushNamed(
      context,
      productDetailsScreenRoute,
      arguments: line.product,
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Consumer<CartProvider>(
      builder: (context, cart, _) {
        if (cart.lines.isEmpty) {
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(defaultPadding * 2),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.shopping_cart_outlined,
                      size: 72, color: theme.colorScheme.outline),
                  const SizedBox(height: defaultPadding),
                  Text(
                    'Your cart is empty',
                    style: theme.textTheme.titleLarge,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Add items from the shop to see them here.',
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            ),
          );
        }

        return Column(
          children: [
            Expanded(
              child: ListView.separated(
                padding: const EdgeInsets.symmetric(
                  horizontal: defaultPadding,
                  vertical: defaultPadding / 2,
                ),
                itemCount: cart.lines.length,
                separatorBuilder: (_, __) => const Divider(height: 1),
                itemBuilder: (context, index) {
                  final line = cart.lines[index];
                  return Padding(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        GestureDetector(
                          onTap: () => _openProduct(context, line),
                          child: ClipRRect(
                            borderRadius:
                                BorderRadius.circular(defaultBorderRadious),
                            child: line.product.image.isEmpty
                                ? Container(
                                    width: 88,
                                    height: 88,
                                    color: theme
                                        .colorScheme.surfaceContainerHighest,
                                    child: Icon(
                                      Icons.image_not_supported_outlined,
                                      color: theme.colorScheme.outline,
                                    ),
                                  )
                                : CachedNetworkImage(
                                    imageUrl: line.product.image,
                                    width: 88,
                                    height: 88,
                                    fit: BoxFit.cover,
                                    placeholder: (_, __) => Container(
                                      width: 88,
                                      height: 88,
                                      color: theme.colorScheme
                                          .surfaceContainerHighest,
                                      child: const Center(
                                        child: SizedBox(
                                          width: 24,
                                          height: 24,
                                          child: CircularProgressIndicator(
                                              strokeWidth: 2),
                                        ),
                                      ),
                                    ),
                                    errorWidget: (_, __, ___) => Container(
                                      width: 88,
                                      height: 88,
                                      color: theme.colorScheme
                                          .surfaceContainerHighest,
                                      child: Icon(Icons.broken_image_outlined,
                                          color: theme.colorScheme.outline),
                                    ),
                                  ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              GestureDetector(
                                onTap: () => _openProduct(context, line),
                                behavior: HitTestBehavior.opaque,
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      line.product.title,
                                      maxLines: 2,
                                      overflow: TextOverflow.ellipsis,
                                      style: theme.textTheme.titleSmall
                                          ?.copyWith(
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      MarketFormat.formatAmount(
                                          line.product.price),
                                      style: theme.textTheme.bodySmall
                                          ?.copyWith(
                                        color: theme
                                            .colorScheme.onSurfaceVariant,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              const SizedBox(height: 8),
                              Row(
                                children: [
                                  _QtyButton(
                                    icon: Icons.remove,
                                    onPressed: () =>
                                        cart.decrementQuantity(index),
                                  ),
                                  Padding(
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 12),
                                    child: Text(
                                      '${line.quantity}',
                                      style: theme.textTheme.titleMedium,
                                    ),
                                  ),
                                  _QtyButton(
                                    icon: Icons.add,
                                    onPressed: () =>
                                        cart.incrementQuantity(index),
                                  ),
                                  const Spacer(),
                                  Text(
                                    MarketFormat.formatAmount(line.lineTotal),
                                    style: theme.textTheme.titleSmall?.copyWith(
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                        IconButton(
                          tooltip: 'Remove',
                          onPressed: () => cart.removeLineAt(index),
                          icon: Icon(
                            Icons.delete_outline,
                            color: theme.colorScheme.error,
                          ),
                        ),
                      ],
                    ),
                  );
                },
              ),
            ),
            Material(
              elevation: 8,
              color: theme.colorScheme.surface,
              child: SafeArea(
                top: false,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(
                    defaultPadding,
                    16,
                    defaultPadding,
                    12,
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'Subtotal (${cart.cartQuantity} items)',
                            style: theme.textTheme.bodyLarge,
                          ),
                          Text(
                            MarketFormat.formatAmount(cart.cartTotal),
                            style: theme.textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      FilledButton(
                        onPressed: () {
                          Navigator.pushNamed(
                            context,
                            checkoutScreenRoute,
                          );
                        },
                        style: FilledButton.styleFrom(
                          backgroundColor: primaryColor,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                        ),
                        child: const Text('Proceed to checkout'),
                      ),
                      TextButton(
                        onPressed: () => cart.removeAllFromCart(),
                        child: Text(
                          'Clear cart',
                          style: TextStyle(color: theme.colorScheme.error),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        );
      },
    );
  }
}

class _QtyButton extends StatelessWidget {
  const _QtyButton({required this.icon, required this.onPressed});

  final IconData icon;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Theme.of(context).colorScheme.surfaceContainerHighest,
      borderRadius: BorderRadius.circular(8),
      child: InkWell(
        onTap: onPressed,
        borderRadius: BorderRadius.circular(8),
        child: SizedBox(
          width: 36,
          height: 36,
          child: Icon(icon, size: 20),
        ),
      ),
    );
  }
}
