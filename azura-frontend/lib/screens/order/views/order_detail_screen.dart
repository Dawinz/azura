import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/constants.dart';
import 'package:shop/core/market_format.dart';
import 'package:shop/route/route_constants.dart';
import 'package:shop/services/storage_service.dart';

/// Order detail from GET /v1/buyer/order.
class OrderDetailScreen extends StatefulWidget {
  const OrderDetailScreen({super.key, required this.orderNumber});

  final String orderNumber;

  @override
  State<OrderDetailScreen> createState() => _OrderDetailScreenState();
}

class _OrderDetailScreenState extends State<OrderDetailScreen> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<Map<String, dynamic>> _load() async {
    final user = await StorageService.getUser();
    final uid = int.tryParse(user?.id ?? '') ?? 0;
    if (uid < 1) {
      throw Exception('Please sign in to view this order.');
    }
    return ApiService.getBuyerOrderDetail(
      userId: uid,
      orderNumber: widget.orderNumber,
    );
  }

  String _fmtMoney(double major) => MarketFormat.formatAmount(major);

  double _centsToMajor(dynamic v) {
    if (v is num) return v.toDouble() / 100.0;
    return (int.tryParse(v?.toString() ?? '') ?? 0) / 100.0;
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(title: Text('Order #${widget.orderNumber}')),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snap.hasError) {
            return Padding(
              padding: const EdgeInsets.all(defaultPadding * 2),
              child: Text(
                snap.error.toString().replaceFirst('Exception: ', ''),
                style: theme.textTheme.bodyLarge,
              ),
            );
          }
          final root = snap.data!;
          final order = root['order'] is Map
              ? Map<String, dynamic>.from(root['order'] as Map)
              : <String, dynamic>{};
          final items = root['items'] is List ? root['items'] as List : const [];
          final ship = root['shipping'] is Map
              ? Map<String, dynamic>.from(root['shipping'] as Map)
              : null;

          final total = double.tryParse(order['price_total']?.toString() ?? '') ?? 0;

          return ListView(
            padding: const EdgeInsets.all(defaultPadding),
            children: [
              Text(
                'Placed ${order['created_at'] ?? ''}',
                style: theme.textTheme.bodyMedium,
              ),
              const SizedBox(height: 8),
              Text(
                'Total: ${_fmtMoney(total)}',
                style: theme.textTheme.titleMedium,
              ),
              Text(
                'Payment: ${order['payment_status'] ?? ''} · ${order['payment_method'] ?? ''}',
                style: theme.textTheme.bodySmall,
              ),
              const Divider(height: defaultPadding * 2),
              Text('Items', style: theme.textTheme.titleSmall),
              const SizedBox(height: 8),
              ...items.map((raw) {
                if (raw is! Map) return const SizedBox.shrink();
                final m = Map<String, dynamic>.from(raw);
                final title = m['title']?.toString() ?? '';
                final qty = m['quantity']?.toString() ?? '1';
                final line = _centsToMajor(m['line_total_cents']);
                final slug = m['slug']?.toString() ?? '';
                return ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(title),
                  subtitle: Text('Qty $qty · ${MarketFormat.formatAmount(line)}'),
                  trailing: slug.isNotEmpty
                      ? TextButton(
                          onPressed: () async {
                            try {
                              final p =
                                  await ApiService.getProductDetails(slug, '0');
                              if (!context.mounted) return;
                              Navigator.pushNamed(
                                context,
                                productDetailsScreenRoute,
                                arguments: p,
                              );
                            } catch (e) {
                              if (!context.mounted) return;
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(
                                  content: Text(
                                    e.toString().replaceFirst('Exception: ', ''),
                                  ),
                                ),
                              );
                            }
                          },
                          child: const Text('View'),
                        )
                      : null,
                );
              }),
              if (ship != null) ...[
                const Divider(height: defaultPadding * 2),
                Text('Shipping', style: theme.textTheme.titleSmall),
                const SizedBox(height: 8),
                Text(
                  '${ship['shipping_first_name'] ?? ''} ${ship['shipping_last_name'] ?? ''}'.trim(),
                ),
                Text(ship['shipping_address']?.toString() ?? ''),
                Text(
                  '${ship['shipping_city'] ?? ''}, ${ship['shipping_state'] ?? ''}, ${ship['shipping_country'] ?? ''}',
                ),
                Text(ship['shipping_phone_number']?.toString() ?? ''),
              ],
            ],
          );
        },
      ),
    );
  }
}
