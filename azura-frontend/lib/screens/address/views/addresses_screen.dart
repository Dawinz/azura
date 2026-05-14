import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/constants.dart';
import 'package:shop/services/storage_service.dart';

/// Default shipping address (saved to `/api/v1/setting/shipping`).
class AddressesScreen extends StatefulWidget {
  const AddressesScreen({super.key});

  @override
  State<AddressesScreen> createState() => _AddressesScreenState();
}

class _AddressesScreenState extends State<AddressesScreen> {
  final _first = TextEditingController();
  final _last = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _addr = TextEditingController();
  final _city = TextEditingController();
  final _zip = TextEditingController();

  List<Map<String, dynamic>> _countries = [];
  List<Map<String, dynamic>> _states = [];
  int? _countryId;
  int? _stateId;
  bool _loading = true;
  bool _saving = false;
  String? _error;

  @override
  void dispose() {
    _first.dispose();
    _last.dispose();
    _email.dispose();
    _phone.dispose();
    _addr.dispose();
    _city.dispose();
    _zip.dispose();
    super.dispose();
  }

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  int? _parseId(dynamic v) {
    if (v is int) return v >= 1 ? v : null;
    return int.tryParse(v?.toString() ?? '');
  }

  Future<void> _bootstrap() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final user = await StorageService.getUser();
      final uid = int.tryParse(user?.id ?? '') ?? 0;
      if (uid < 1) {
        setState(() {
          _loading = false;
          _error = 'Please sign in to manage your address.';
        });
        return;
      }
      final countries = await ApiService.getLocationCountries();
      dynamic ship;
      try {
        ship = await ApiService.getShippingAddress(uid);
      } catch (_) {
        ship = null;
      }
      if (ship is Map) {
        _first.text = ship['first_name']?.toString() ?? '';
        _last.text = ship['last_name']?.toString() ?? '';
        _email.text = ship['email']?.toString() ?? '';
        _phone.text = ship['phone_number']?.toString() ?? '';
        _addr.text = ship['address']?.toString() ?? '';
        _city.text = ship['city']?.toString() ?? '';
        _zip.text = ship['zip_code']?.toString() ?? '';
        _countryId = _parseId(ship['country_id']);
        _stateId = _parseId(ship['state_id']);
      }
      if (_countryId == null && countries.isNotEmpty) {
        int? tz;
        for (final c in countries) {
          final n = c['name']?.toString().toLowerCase() ?? '';
          if (n.contains('tanzania')) {
            tz = _parseId(c['id']);
            break;
          }
        }
        _countryId = tz ?? _parseId(countries.first['id']);
      }
      if (_countryId != null) {
        _states = await ApiService.getLocationStates(_countryId!);
        if (_stateId == null && _states.isNotEmpty) {
          _stateId = _parseId(_states.first['id']);
        }
      }
      if (!mounted) return;
      setState(() {
        _countries = countries;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = e.toString();
      });
    }
  }

  Future<void> _save() async {
    final user = await StorageService.getUser();
    if (!mounted) return;
    final uid = int.tryParse(user?.id ?? '') ?? 0;
    if (uid < 1) return;
    if (_countryId == null || _stateId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Select country and region.')),
      );
      return;
    }
    setState(() => _saving = true);
    try {
      await ApiService.updateShippingAddress(
        uid,
        _first.text.trim(),
        _last.text.trim(),
        _email.text.trim(),
        _phone.text.trim(),
        _addr.text.trim(),
        _countryId!,
        _stateId.toString(),
        _city.text.trim(),
        _zip.text.trim(),
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Address saved')),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString())),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(title: const Text('Shipping address')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!, textAlign: TextAlign.center))
              : ListView(
                  padding: const EdgeInsets.all(defaultPadding),
                  children: [
                    TextField(
                      controller: _first,
                      decoration: const InputDecoration(
                        labelText: 'First name',
                        border: OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _last,
                      decoration: const InputDecoration(
                        labelText: 'Last name',
                        border: OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _email,
                      keyboardType: TextInputType.emailAddress,
                      decoration: const InputDecoration(
                        labelText: 'Email',
                        border: OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _phone,
                      keyboardType: TextInputType.phone,
                      decoration: const InputDecoration(
                        labelText: 'Phone',
                        border: OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _addr,
                      maxLines: 2,
                      decoration: const InputDecoration(
                        labelText: 'Street address',
                        border: OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _city,
                      decoration: const InputDecoration(
                        labelText: 'City',
                        border: OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _zip,
                      decoration: const InputDecoration(
                        labelText: 'Postal / ZIP code',
                        border: OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 16),
                    DropdownButtonFormField<int>(
                      value: _countryId,
                      decoration: const InputDecoration(
                        labelText: 'Country',
                        border: OutlineInputBorder(),
                      ),
                      items: _countries
                          .map((c) {
                            final id = _parseId(c['id']);
                            if (id == null) return null;
                            return DropdownMenuItem(
                              value: id,
                              child: Text(c['name']?.toString() ?? ''),
                            );
                          })
                          .whereType<DropdownMenuItem<int>>()
                          .toList(),
                      onChanged: (v) async {
                        if (v == null) return;
                        setState(() {
                          _countryId = v;
                          _stateId = null;
                        });
                        final st = await ApiService.getLocationStates(v);
                        if (!mounted) return;
                        setState(() {
                          _states = st;
                          _stateId =
                              st.isNotEmpty ? _parseId(st.first['id']) : null;
                        });
                      },
                    ),
                    const SizedBox(height: 16),
                    DropdownButtonFormField<int>(
                      value: _stateId,
                      decoration: const InputDecoration(
                        labelText: 'Region',
                        border: OutlineInputBorder(),
                      ),
                      items: _states
                          .map((s) {
                            final id = _parseId(s['id']);
                            if (id == null) return null;
                            return DropdownMenuItem(
                              value: id,
                              child: Text(s['name']?.toString() ?? ''),
                            );
                          })
                          .whereType<DropdownMenuItem<int>>()
                          .toList(),
                      onChanged: (v) => setState(() => _stateId = v),
                    ),
                    const SizedBox(height: 24),
                    FilledButton(
                      onPressed: _saving ? null : _save,
                      child: _saving
                          ? const SizedBox(
                              width: 22,
                              height: 22,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Text('Save address'),
                    ),
                    const SizedBox(height: 12),
                    Text(
                      'This address is reused for checkout when you are signed in.',
                      style: theme.textTheme.bodySmall,
                    ),
                  ],
                ),
    );
  }
}
