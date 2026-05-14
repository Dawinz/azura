import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/components/order_card.dart';
import 'package:shop/constants.dart';
import 'package:shop/models/order_model.dart';
import 'package:shop/route/route_constants.dart';
import 'package:shop/services/storage_service.dart';

class OrdersScreen extends StatefulWidget {
  const OrdersScreen({super.key});

  @override
  State<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen> {
  late Future<List<OrderModel>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<List<OrderModel>> _load() async {
    final user = await StorageService.getUser();
    final uid = int.tryParse(user?.id ?? '') ?? 0;
    if (uid < 1) {
      throw Exception('Please sign in to see your orders.');
    }
    return ApiService.getBuyerOrders(uid);
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
        title: const Text('My orders'),
      ),
      body: RefreshIndicator(
        onRefresh: _reload,
        child: FutureBuilder<List<OrderModel>>(
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
                    snapshot.error.toString().replaceFirst('Exception: ', ''),
                    style: theme.textTheme.bodyLarge,
                  ),
                ],
              );
            }
            final orders = snapshot.data ?? [];
            if (orders.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(defaultPadding * 2),
                children: [
                  Text(
                    'No orders yet. Purchases you make in the app will show up here.',
                    style: theme.textTheme.bodyLarge,
                    textAlign: TextAlign.center,
                  ),
                ],
              );
            }
            return ListView.separated(
              padding: const EdgeInsets.all(defaultPadding),
              itemCount: orders.length,
              separatorBuilder: (context, index) =>
                  const SizedBox(height: defaultPadding),
              itemBuilder: (context, index) {
                final order = orders[index];
                return OrderCard(
                  order: order,
                  press: () {
                    final n = order.orderNumber;
                    if (n == null || n.isEmpty) return;
                    Navigator.pushNamed(
                      context,
                      orderDetailsScreenRoute,
                      arguments: n,
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
