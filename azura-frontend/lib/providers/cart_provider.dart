import 'package:flutter/material.dart';
import 'package:shop/models/product_model.dart';

/// One row in the shopping cart (product + quantity).
class CartLine {
  CartLine({required this.product, this.quantity = 1});

  final ProductModel product;
  int quantity;

  double get lineTotal => product.price * quantity;
}

class CartProvider with ChangeNotifier {
  final List<CartLine> _lines = [];

  /// Distinct cart lines (merge by [ProductModel.id] on add).
  List<CartLine> get lines => List.unmodifiable(_lines);

  /// Total number of units across all lines (for tab badge).
  int get cartQuantity =>
      _lines.fold(0, (sum, line) => sum + line.quantity);

  double get cartTotal =>
      _lines.fold(0.0, (sum, line) => sum + line.lineTotal);

  /// Backward-compatible flat list (one entry per unit). Prefer [lines] in UI.
  List<ProductModel> get cartItems =>
      _lines.expand((l) => List<ProductModel>.filled(l.quantity, l.product)).toList();

  /// Returns false if the product cannot be sold in the app (e.g. digital).
  bool addToCart(ProductModel product) {
    if (!product.isPurchasableInApp) return false;
    final idx = _lines.indexWhere((l) => l.product.id == product.id);
    if (idx >= 0) {
      _lines[idx].quantity += 1;
    } else {
      _lines.add(CartLine(product: product));
    }
    notifyListeners();
    return true;
  }

  void incrementQuantity(int lineIndex) {
    if (lineIndex < 0 || lineIndex >= _lines.length) return;
    _lines[lineIndex].quantity += 1;
    notifyListeners();
  }

  void decrementQuantity(int lineIndex) {
    if (lineIndex < 0 || lineIndex >= _lines.length) return;
    if (_lines[lineIndex].quantity > 1) {
      _lines[lineIndex].quantity -= 1;
    } else {
      _lines.removeAt(lineIndex);
    }
    notifyListeners();
  }

  void removeLineAt(int lineIndex) {
    if (lineIndex < 0 || lineIndex >= _lines.length) return;
    _lines.removeAt(lineIndex);
    notifyListeners();
  }

  void removeFromCart(ProductModel product) {
    _lines.removeWhere((l) => l.product.id == product.id);
    notifyListeners();
  }

  void removeAllFromCart() {
    _lines.clear();
    notifyListeners();
  }
}
