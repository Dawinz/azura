import 'package:shop/models/product_model.dart';

class OrderItemModel {
  final ProductModel product;
  final int quantity;

  OrderItemModel({required this.product, required this.quantity});
}

class OrderModel {
  final String id;
  final String date;
  final List<OrderItemModel> items;
  final double total;
  final String status;
  final String? currency;
  final String? orderNumber;
  final String? paymentStatus;

  OrderModel({
    required this.id,
    required this.date,
    required this.items,
    required this.total,
    required this.status,
    this.currency,
    this.orderNumber,
    this.paymentStatus,
  });

  /// Row from GET /v1/buyer/orders.
  factory OrderModel.fromBuyerOrdersJson(Map<String, dynamic> json) {
    final rawItems = json['items'];
    final List<OrderItemModel> lines = [];
    if (rawItems is List) {
      for (final e in rawItems) {
        if (e is! Map) continue;
        final m = Map<String, dynamic>.from(e);
        lines.add(OrderItemModel(
          product: ProductModel.fromOrderLine(m),
          quantity: (m['quantity'] is int)
              ? m['quantity'] as int
              : int.tryParse('${m['quantity'] ?? 1}') ?? 1,
        ));
      }
    }
    final on = json['order_number']?.toString() ?? '';
    final totalRaw = json['price_total'];
    final double total = totalRaw is num
        ? totalRaw.toDouble()
        : double.tryParse(totalRaw?.toString() ?? '') ?? 0;
    final st = (json['status'] is int) ? json['status'] as int : int.tryParse('${json['status'] ?? 0}') ?? 0;
    final pay = json['payment_status']?.toString() ?? '';
    final statusLabel = st == 1 ? 'Completed' : 'In progress';
    final created = json['created_at']?.toString() ?? '';
    return OrderModel(
      id: on.isNotEmpty ? '#$on' : '—',
      date: created.length >= 10 ? created.substring(0, 10) : created,
      items: lines,
      total: total,
      status: pay.isNotEmpty ? '$statusLabel · $pay' : statusLabel,
      currency: json['price_currency']?.toString(),
      orderNumber: on,
      paymentStatus: pay,
    );
  }
}
