import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/constants.dart';
import 'package:shop/core/market_format.dart';
import 'package:shop/providers/cart_provider.dart';
import 'package:shop/route/route_constants.dart';
import 'package:shop/services/storage_service.dart';
import 'package:url_launcher/url_launcher.dart';

/// Order review and Selcom hosted checkout (opens system browser / WebView).
class CheckoutScreen extends StatefulWidget {
  const CheckoutScreen({super.key});

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  static const Set<String> _trustedHosts = {
    'azuramall.shop',
    'www.azuramall.shop',
    'selcommobile.com',
    'www.selcommobile.com',
    'apigw.selcommobile.com',
  };

  bool _busy = false;

  bool _isTrustedPaymentUrl(Uri uri) {
    final host = uri.host.toLowerCase();
    if (host.isEmpty) return false;
    if (_trustedHosts.contains(host)) return true;
    return host.endsWith('.selcommobile.com') ||
        host.endsWith('.azuramall.shop');
  }

  String _formatTotal(CartProvider cart) {
    return MarketFormat.formatAmount(
      cart.lines.isEmpty ? 0 : cart.cartTotal,
    );
  }

  Future<void> _completePurchase(BuildContext context, CartProvider cart) async {
    final messenger = ScaffoldMessenger.of(context);
    final nav = Navigator.of(context);
    final user = await StorageService.getUser();
    if (!mounted) return;
    if (user == null || user.id.isEmpty) {
      messenger.showSnackBar(
        const SnackBar(
          content: Text('Please sign in to complete checkout.'),
          behavior: SnackBarBehavior.floating,
        ),
      );
      nav.pushNamed(logInScreenRoute);
      return;
    }

    final lines = <Map<String, dynamic>>[];
    for (final line in cart.lines) {
      final pid = int.tryParse(line.product.id) ?? 0;
      if (pid < 1) continue;
      lines.add({'product_id': pid, 'quantity': line.quantity});
    }
    if (lines.isEmpty) {
      messenger.showSnackBar(
        const SnackBar(
          content: Text('Cart items are missing product IDs. Try reopening the product from the shop.'),
          behavior: SnackBarBehavior.floating,
        ),
      );
      return;
    }

    for (final line in cart.lines) {
      if (!line.product.isPurchasableInApp) {
        messenger.showSnackBar(
          const SnackBar(
            content: Text(
              'Your cart contains items that cannot be purchased in the app. Remove them or shop on azuramall.shop in a browser.',
            ),
            behavior: SnackBarBehavior.floating,
          ),
        );
        return;
      }
    }

    setState(() => _busy = true);
    try {
      final result = await ApiService.initSelcomCheckout(
        lines: lines,
        buyerName: user.name,
        buyerEmail: user.email,
        bearerToken: user.token,
      );
      if (!mounted) return;
      final uri = Uri.parse(result.paymentGatewayUrl);
      if ((uri.scheme != 'https' && uri.scheme != 'http') ||
          !_isTrustedPaymentUrl(uri)) {
        messenger.showSnackBar(
          const SnackBar(
            content: Text('Payment link is invalid. Please try again.'),
            behavior: SnackBarBehavior.floating,
          ),
        );
        return;
      }
      final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (!ok) {
        messenger.showSnackBar(
          const SnackBar(
            content: Text('Could not open the payment page. Try again or use azuramall.shop in a browser.'),
            behavior: SnackBarBehavior.floating,
          ),
        );
      } else {
        messenger.showSnackBar(
          const SnackBar(
            content: Text(
              'Complete payment in the browser. When finished, you can return to the app — your order will appear on azuramall.shop.',
            ),
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    } catch (e) {
      if (!mounted) return;
      messenger.showSnackBar(
        SnackBar(
          content: Text(e.toString().replaceFirst('Exception: ', '')),
          behavior: SnackBarBehavior.floating,
        ),
      );
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Checkout'),
      ),
      body: Consumer<CartProvider>(
        builder: (context, cart, _) {
          if (cart.lines.isEmpty) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(defaultPadding * 2),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      'Your cart is empty',
                      style: theme.textTheme.titleLarge,
                    ),
                    const SizedBox(height: defaultPadding),
                    FilledButton(
                      onPressed: () => Navigator.pop(context),
                      child: const Text('Back to shop'),
                    ),
                  ],
                ),
              ),
            );
          }

          return ListView(
            padding: const EdgeInsets.all(defaultPadding),
            children: [
              Text(
                'Order summary',
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: defaultPadding),
              ...cart.lines.map((line) {
                return Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Text(
                          '${line.product.title} × ${line.quantity}',
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      Text(
                        MarketFormat.formatAmount(line.lineTotal),
                        style: theme.textTheme.bodyLarge?.copyWith(
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                );
              }),
              const Divider(height: 32),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Total',
                    style: theme.textTheme.titleMedium,
                  ),
                  Text(
                    _formatTotal(cart),
                    style: theme.textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: defaultPadding * 2),
              Text(
                'Pay securely with Selcom. You may need to sign in on azuramall.shop after payment to view the order.',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
              const SizedBox(height: defaultPadding * 2),
              FilledButton(
                onPressed: _busy
                    ? null
                    : () => _completePurchase(context, cart),
                style: FilledButton.styleFrom(
                  backgroundColor: primaryColor,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                ),
                child: _busy
                    ? const SizedBox(
                        height: 22,
                        width: 22,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Complete purchase'),
              ),
              const SizedBox(height: 12),
              OutlinedButton(
                onPressed: _busy ? null : () => Navigator.pop(context),
                child: const Text('Back to cart'),
              ),
            ],
          );
        },
      ),
    );
  }
}
