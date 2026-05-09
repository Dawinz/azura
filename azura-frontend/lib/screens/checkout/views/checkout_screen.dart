import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/constants.dart';
import 'package:shop/core/market_format.dart';
import 'package:shop/providers/cart_provider.dart';
import 'package:shop/route/route_constants.dart';
import 'package:shop/services/storage_service.dart';
import 'package:url_launcher/url_launcher.dart';

/// Order review, shipping address (when required), and Selcom hosted checkout.
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

  final _firstName = TextEditingController();
  final _lastName = TextEditingController();
  final _phone = TextEditingController();
  final _address = TextEditingController();
  final _city = TextEditingController();
  final _zip = TextEditingController();

  bool _busy = false;
  List<Map<String, dynamic>> _countries = [];
  List<Map<String, dynamic>> _states = [];
  int? _countryId;
  int? _stateId;
  bool _loadingLocations = true;
  String? _locationError;

  @override
  void initState() {
    super.initState();
    _loadCountries();
  }

  @override
  void dispose() {
    _firstName.dispose();
    _lastName.dispose();
    _phone.dispose();
    _address.dispose();
    _city.dispose();
    _zip.dispose();
    super.dispose();
  }

  Future<void> _loadCountries() async {
    setState(() {
      _loadingLocations = true;
      _locationError = null;
    });
    try {
      final list = await ApiService.getLocationCountries();
      if (!mounted) return;
      int? tanzania;
      for (final c in list) {
        final name = c['name']?.toString().toLowerCase() ?? '';
        if (name.contains('tanzania')) {
          tanzania = c['id'] is int ? c['id'] as int : int.tryParse('${c['id']}');
          break;
        }
      }
      setState(() {
        _countries = list;
        _countryId = tanzania ??
            (list.isNotEmpty
                ? (list.first['id'] is int
                    ? list.first['id'] as int
                    : int.tryParse('${list.first['id']}'))
                : null);
        _loadingLocations = false;
      });
      if (_countryId != null) {
        await _loadStates(_countryId!);
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loadingLocations = false;
        _locationError = e.toString();
      });
    }
  }

  Future<void> _loadStates(int countryId) async {
    try {
      final list = await ApiService.getLocationStates(countryId);
      if (!mounted) return;
      setState(() {
        _states = list;
        _stateId = list.isNotEmpty
            ? (list.first['id'] is int
                ? list.first['id'] as int
                : int.tryParse('${list.first['id']}'))
            : null;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _states = [];
        _stateId = null;
      });
    }
  }

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

  bool _shippingFieldsOk() {
    if (_firstName.text.trim().isEmpty || _lastName.text.trim().isEmpty) {
      return false;
    }
    if (_phone.text.trim().isEmpty ||
        _address.text.trim().isEmpty ||
        _city.text.trim().isEmpty) {
      return false;
    }
    if (_countryId == null || _stateId == null) {
      return false;
    }
    return true;
  }

  Map<String, dynamic>? _shippingPayload(String buyerEmail) {
    if (!_shippingFieldsOk()) return null;
    return {
      'first_name': _firstName.text.trim(),
      'last_name': _lastName.text.trim(),
      'email': buyerEmail,
      'phone_number': _phone.text.trim(),
      'address': _address.text.trim(),
      'country_id': _countryId,
      'state_id': _stateId,
      'city': _city.text.trim(),
      'zip_code': _zip.text.trim(),
    };
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
          content: Text(
            'Cart items are missing product IDs. Try reopening the product from the shop.',
          ),
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

    final shipping = _shippingPayload(user.email);
    if (shipping == null) {
      messenger.showSnackBar(
        const SnackBar(
          content: Text(
            'Please complete shipping: name, phone, address, country and region.',
          ),
          behavior: SnackBarBehavior.floating,
        ),
      );
      return;
    }

    setState(() => _busy = true);
    try {
      final result = await ApiService.initSelcomCheckout(
        lines: lines,
        buyerName:
            '${_firstName.text.trim()} ${_lastName.text.trim()}'.trim().isEmpty
                ? user.name
                : '${_firstName.text.trim()} ${_lastName.text.trim()}',
        buyerEmail: user.email,
        buyerPhone: _phone.text.trim().isNotEmpty ? _phone.text.trim() : null,
        bearerToken: user.token,
        shippingAddress: shipping,
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
            content: Text(
              'Could not open the payment page. Please try again.',
            ),
            behavior: SnackBarBehavior.floating,
          ),
        );
      } else {
        messenger.showSnackBar(
          const SnackBar(
            content: Text(
              'Complete payment in the secure browser window. When finished, return here — your order will sync automatically.',
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
                'Delivery',
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 8),
              if (_locationError != null)
                Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: Text(
                    _locationError!,
                    style: TextStyle(color: theme.colorScheme.error),
                  ),
                ),
              if (_loadingLocations)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 12),
                  child: LinearProgressIndicator(),
                ),
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _firstName,
                      decoration: const InputDecoration(
                        labelText: 'First name',
                        border: OutlineInputBorder(),
                      ),
                      textCapitalization: TextCapitalization.words,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: TextField(
                      controller: _lastName,
                      decoration: const InputDecoration(
                        labelText: 'Last name',
                        border: OutlineInputBorder(),
                      ),
                      textCapitalization: TextCapitalization.words,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _phone,
                decoration: const InputDecoration(
                  labelText: 'Phone',
                  border: OutlineInputBorder(),
                ),
                keyboardType: TextInputType.phone,
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _address,
                decoration: const InputDecoration(
                  labelText: 'Street address',
                  border: OutlineInputBorder(),
                ),
                maxLines: 2,
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _city,
                      decoration: const InputDecoration(
                        labelText: 'City',
                        border: OutlineInputBorder(),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: TextField(
                      controller: _zip,
                      decoration: const InputDecoration(
                        labelText: 'Postal / ZIP',
                        border: OutlineInputBorder(),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              InputDecorator(
                decoration: const InputDecoration(
                  labelText: 'Country',
                  border: OutlineInputBorder(),
                ),
                child: DropdownButtonHideUnderline(
                  child: DropdownButton<int>(
                    isExpanded: true,
                    value: _countryId,
                    hint: const Text('Select country'),
                    items: _countries
                        .map((c) {
                          final raw = c['id'];
                          final id = raw is int
                              ? raw
                              : int.tryParse('${raw ?? ''}');
                          if (id == null || id < 1) return null;
                          return DropdownMenuItem<int>(
                            value: id,
                            child: Text(c['name']?.toString() ?? ''),
                          );
                        })
                        .whereType<DropdownMenuItem<int>>()
                        .toList(),
                    onChanged: _loadingLocations
                        ? null
                        : (v) async {
                            if (v == null) return;
                            setState(() {
                              _countryId = v;
                              _stateId = null;
                              _states = [];
                            });
                            await _loadStates(v);
                          },
                  ),
                ),
              ),
              const SizedBox(height: 12),
              InputDecorator(
                decoration: const InputDecoration(
                  labelText: 'Region / state',
                  border: OutlineInputBorder(),
                ),
                child: DropdownButtonHideUnderline(
                  child: DropdownButton<int>(
                    isExpanded: true,
                    value: _stateId,
                    hint: const Text('Select region'),
                    items: _states
                        .map((s) {
                          final raw = s['id'];
                          final id = raw is int
                              ? raw
                              : int.tryParse('${raw ?? ''}');
                          if (id == null || id < 1) return null;
                          return DropdownMenuItem<int>(
                            value: id,
                            child: Text(s['name']?.toString() ?? ''),
                          );
                        })
                        .whereType<DropdownMenuItem<int>>()
                        .toList(),
                    onChanged: _states.isEmpty
                        ? null
                        : (v) => setState(() => _stateId = v),
                  ),
                ),
              ),
              const SizedBox(height: defaultPadding * 2),
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
                    'Subtotal',
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
              const SizedBox(height: 8),
              Text(
                'Shipping is calculated on pay — total shown by Selcom includes delivery when your sellers ship to the selected region.',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
              const SizedBox(height: defaultPadding * 2),
              Text(
                'Pay securely with Selcom in your browser.',
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
