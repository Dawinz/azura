import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/constants.dart';
import 'package:shop/models/user_model.dart';
import 'package:shop/services/storage_service.dart';

/// Edit username, email, and public slug (GET/POST settings profile).
class UserInfoScreen extends StatefulWidget {
  const UserInfoScreen({super.key});

  @override
  State<UserInfoScreen> createState() => _UserInfoScreenState();
}

class _UserInfoScreenState extends State<UserInfoScreen> {
  final _username = TextEditingController();
  final _email = TextEditingController();
  final _slug = TextEditingController();
  int _notifyMsg = 0;
  bool _loading = true;
  bool _saving = false;
  String? _error;

  @override
  void dispose() {
    _username.dispose();
    _email.dispose();
    _slug.dispose();
    super.dispose();
  }

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
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
          _error = 'Please sign in.';
        });
        return;
      }
      final raw = await ApiService.getProfile(uid);
      if (raw is Map<String, dynamic>) {
        _username.text = raw['username']?.toString() ?? '';
        _email.text = raw['email']?.toString() ?? '';
        _slug.text = raw['slug']?.toString() ?? '';
        _notifyMsg = raw['send_email_new_message'] is int
            ? raw['send_email_new_message'] as int
            : int.tryParse('${raw['send_email_new_message'] ?? 0}') ?? 0;
      }
      if (!mounted) return;
      setState(() => _loading = false);
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
    if (user == null) return;
    final uid = int.tryParse(user.id) ?? 0;
    if (uid < 1) return;
    setState(() => _saving = true);
    try {
      await ApiService.updateProfile(
        uid,
        _username.text.trim(),
        _email.text.trim(),
        _slug.text.trim(),
        _notifyMsg,
      );
      final m = Map<String, dynamic>.from(user.toMap());
      m['username'] = _username.text.trim();
      m['email'] = _email.text.trim();
      m['slug'] = _slug.text.trim();
      await StorageService.saveUser(UserModel.fromMap(m));
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Profile updated')),
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
      appBar: AppBar(title: const Text('Account')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!))
              : ListView(
                  padding: const EdgeInsets.all(defaultPadding),
                  children: [
                    TextField(
                      controller: _username,
                      decoration: const InputDecoration(
                        labelText: 'Username',
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
                      controller: _slug,
                      decoration: const InputDecoration(
                        labelText: 'Public shop slug',
                        border: OutlineInputBorder(),
                        helperText:
                            'Used in your shop URL. Changing it may break old links.',
                      ),
                    ),
                    const SizedBox(height: 8),
                    SwitchListTile(
                      title: const Text('Email me for new messages'),
                      value: _notifyMsg == 1,
                      onChanged: (v) {
                        setState(() => _notifyMsg = v ? 1 : 0);
                      },
                    ),
                    const SizedBox(height: 16),
                    FilledButton(
                      onPressed: _saving ? null : _save,
                      child: _saving
                          ? const SizedBox(
                              width: 22,
                              height: 22,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Text('Save'),
                    ),
                    const SizedBox(height: 12),
                    Text(
                      'For password changes, use the profile screen or website settings.',
                      style: theme.textTheme.bodySmall,
                    ),
                  ],
                ),
    );
  }
}
