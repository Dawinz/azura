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

  OrderModel({
    required this.id,
    required this.date,
    required this.items,
    required this.total,
    required this.status,
  });
}
