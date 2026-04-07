
import 'package:flutter/material.dart';
import 'package:shop/models/product_model.dart';

class CartProvider with ChangeNotifier {
  final List<ProductModel> _cartItems = [];

  List<ProductModel> get cartItems => _cartItems;

  void addToCart(ProductModel product) {
    _cartItems.add(product);
    notifyListeners();
  }

  void removeFromCart(ProductModel product) {
    _cartItems.remove(product);
    notifyListeners();
  }

  void removeAllFromCart() {
    _cartItems.clear();
    notifyListeners();
  }

  int get cartQuantity => _cartItems.length;

  double get cartTotal {
    return _cartItems.fold(0, (previousValue, element) => previousValue + element.price);
  }
}
