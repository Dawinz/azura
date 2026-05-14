import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/constants.dart';
import 'package:shop/core/market_format.dart';
import 'package:shop/services/storage_service.dart';

import 'components/wallet_balance_card.dart';

/// Seller balance and recent earnings/payouts from `/v1/wallet/summary`.
class WalletScreen extends StatefulWidget {
  const WalletScreen({super.key});

  @override
  State<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends State<WalletScreen> {
  late Future<WalletSummary> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<WalletSummary> _load() async {
    final user = await StorageService.getUser();
    final uid = int.tryParse(user?.id ?? '') ?? 0;
    if (uid < 1) {
      throw StateError('signed_out');
    }
    return ApiService.getWalletSummary(uid);
  }

  Future<void> _reload() async {
    setState(() {
      _future = _load();
    });
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(
        title: const Text('Wallet'),
      ),
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _reload,
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: defaultPadding),
            child: FutureBuilder<WalletSummary>(
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
                          'Sign in to view your seller balance.',
                          style: theme.textTheme.bodyLarge,
                          textAlign: TextAlign.center,
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
                        style: theme.textTheme.bodyLarge,
                        textAlign: TextAlign.center,
                      ),
                    ],
                  );
                }
                final data = snapshot.data!;
                final major = data.balanceCents / 100.0;
                return ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  children: [
                    WalletBalanceCard(
                      balance: major,
                      onTabChargeBalance: () {
                        showDialog<void>(
                          context: context,
                          builder: (ctx) => AlertDialog(
                            title: const Text('Seller balance'),
                            content: const Text(
                              'Your balance grows when orders are completed. '
                              'Withdrawals and payout settings are managed on the seller dashboard at azuramall.shop.',
                            ),
                            actions: [
                              TextButton(
                                onPressed: () => Navigator.pop(ctx),
                                child: const Text('OK'),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
                    const SizedBox(height: defaultPadding * 2),
                    Text(
                      'Recent activity',
                      style: theme.textTheme.titleSmall,
                    ),
                    if (data.history.isEmpty)
                      Padding(
                        padding: const EdgeInsets.only(top: defaultPadding * 2),
                        child: Text(
                          'No earnings or payouts recorded yet.',
                          style: theme.textTheme.bodyMedium,
                          textAlign: TextAlign.center,
                        ),
                      )
                    else
                      ...data.history.map((h) {
                        final amt = h.amountCents / 100.0;
                        final sign = h.amountCents >= 0 ? '+' : '';
                        final date = h.createdAt.length >= 10
                            ? h.createdAt.substring(0, 10)
                            : h.createdAt;
                        return Column(
                          children: [
                            const Divider(height: 1),
                            ListTile(
                              contentPadding: EdgeInsets.zero,
                              leading: Icon(
                                h.type == 'payout'
                                    ? Icons.payments_outlined
                                    : Icons.trending_up,
                              ),
                              title: Text(h.label),
                              subtitle: Text(date),
                              trailing: Text(
                                '$sign${MarketFormat.formatAmount(amt.abs())}',
                                style: theme.textTheme.titleSmall?.copyWith(
                                  color: h.amountCents >= 0
                                      ? Colors.green.shade700
                                      : theme.colorScheme.error,
                                ),
                              ),
                            ),
                          ],
                        );
                      }),
                    const SizedBox(height: 80),
                  ],
                );
              },
            ),
          ),
        ),
      ),
    );
  }
}
