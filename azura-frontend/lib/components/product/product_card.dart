import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shop/core/market_format.dart';
import 'package:shop/models/product_model.dart';
import 'package:shop/providers/cart_provider.dart';

import '../../../constants.dart';

class ProductCard extends StatelessWidget {
  const ProductCard({
    super.key,
    required this.product,
    this.press,
  });

  final ProductModel product;
  final VoidCallback? press;

  @override
  Widget build(BuildContext context) {
    final cartProvider = Provider.of<CartProvider>(context, listen: false);

    return Container(
      padding: const EdgeInsets.all(defaultPadding / 2),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: Colors.black12),
        borderRadius:
            const BorderRadius.all(Radius.circular(defaultBorderRadious)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: GestureDetector(
              onTap: press,
              behavior: HitTestBehavior.opaque,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Container(
                      width: double.infinity,
                      decoration: const BoxDecoration(
                        color: Colors.black12,
                        borderRadius: BorderRadius.all(
                          Radius.circular(defaultBorderRadious),
                        ),
                      ),
                      clipBehavior: Clip.antiAlias,
                      child: product.image.isEmpty
                          ? const ColoredBox(
                              color: Colors.black12,
                              child: Center(
                                child: Icon(Icons.image_not_supported_outlined),
                              ),
                            )
                          : Image.network(
                              product.image,
                              fit: BoxFit.cover,
                              errorBuilder: (_, __, ___) => const ColoredBox(
                                color: Colors.black12,
                                child: Center(
                                  child: Icon(Icons.broken_image_outlined),
                                ),
                              ),
                            ),
                    ),
                  ),
                  const SizedBox(height: defaultPadding / 2),
                  Text(
                    product.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontWeight: FontWeight.w500,
                      fontSize: 13,
                      height: 1.2,
                    ),
                  ),
                ],
              ),
            ),
          ),
          Row(
            children: [
              Expanded(
                child: GestureDetector(
                  onTap: press,
                  behavior: HitTestBehavior.opaque,
                  child: Text(
                    MarketFormat.formatAmount(product.price),
                    style: const TextStyle(fontWeight: FontWeight.bold),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ),
              IconButton(
                tooltip: 'Add to cart',
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(
                  minWidth: 40,
                  minHeight: 40,
                ),
                visualDensity: VisualDensity.compact,
                onPressed: () {
                  final ok = cartProvider.addToCart(product);
                  if (!context.mounted) return;
                  ScaffoldMessenger.maybeOf(context)?.showSnackBar(
                    SnackBar(
                      content: Text(
                        ok
                            ? 'Added to cart'
                            : 'This item is not available for purchase in the app. Use azuramall.shop in a browser.',
                      ),
                      behavior: SnackBarBehavior.floating,
                    ),
                  );
                },
                icon: const Icon(Icons.add_shopping_cart_outlined, size: 22),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
