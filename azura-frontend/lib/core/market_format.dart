import 'package:intl/intl.dart';

/// Azura is a Tanzania marketplace: list prices are shown in Tanzanian Shillings.
class MarketFormat {
  MarketFormat._();

  static final NumberFormat _tzs = NumberFormat.currency(
    locale: 'en_TZ',
    symbol: 'TSh ',
    decimalDigits: 0,
  );

  static String formatAmount(double amount) => _tzs.format(amount);
}
