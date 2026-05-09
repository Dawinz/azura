import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/models/user_model.dart';
import 'package:shop/route/route_constants.dart';
import 'package:shop/screens/support/support_center_screen.dart';
import 'package:shop/services/storage_service.dart';

import '../../../constants.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  UserModel? _user;
  bool _loadingUser = true;

  @override
  void initState() {
    super.initState();
    _loadUser();
  }

  Future<void> _loadUser() async {
    final user = await StorageService.getUser();
    if (mounted) {
      setState(() {
        _user = user;
        _loadingUser = false;
      });
    }
  }

  Future<void> _confirmDeleteAccount(UserModel user) async {
    String password = '';
    final confirmed = await showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (ctx) {
        return AlertDialog(
          title: const Text('Delete account'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'This permanently deletes your account and associated seller listings. '
                  'This cannot be undone.',
                ),
                const SizedBox(height: defaultPadding),
                TextField(
                  obscureText: true,
                  onChanged: (value) => password = value,
                  decoration: const InputDecoration(
                    labelText: 'Confirm password',
                    border: OutlineInputBorder(),
                  ),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Cancel'),
            ),
            TextButton(
              onPressed: () => Navigator.pop(ctx, true),
              style: TextButton.styleFrom(foregroundColor: Colors.red),
              child: const Text('Delete'),
            ),
          ],
        );
      },
    );
    if (confirmed != true || !mounted) return;
    final messenger = ScaffoldMessenger.of(context);
    try {
      await ApiService.deleteAccount(
        userId: user.id,
        email: user.email,
        password: password,
      );
      await StorageService.removeUser();
      if (!mounted) return;
      messenger.showSnackBar(
        const SnackBar(content: Text('Your account has been deleted')),
      );
      Navigator.of(context).pushNamedAndRemoveUntil(
        logInScreenRoute,
        (route) => false,
      );
    } catch (e) {
      if (!mounted) return;
      messenger.showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loadingUser) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      );
    }

    if (_user == null) {
      return Scaffold(
        body: SafeArea(
          child: Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 480),
              child: Padding(
                padding: const EdgeInsets.all(defaultPadding),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Text(
                      'Profile',
                      style: Theme.of(context).textTheme.headlineSmall,
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: defaultPadding),
                    const Text(
                      'Browse the shop without signing in. Create an account when you want '
                      'personal features such as order history tied to your profile.',
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: defaultPadding * 2),
                    ElevatedButton(
                      onPressed: () {
                        Navigator.pushNamed(context, logInScreenRoute);
                      },
                      child: const Text('Log in'),
                    ),
                    const SizedBox(height: defaultPadding),
                    OutlinedButton(
                      onPressed: () {
                        Navigator.pushNamed(context, signUpScreenRoute);
                      },
                      child: const Text('Sign up'),
                    ),
                    const SizedBox(height: defaultPadding * 2),
                    TextButton(
                      onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute<void>(
                            builder: (context) => const SupportCenterScreen(),
                          ),
                        );
                      },
                      child: const Text('Help & support'),
                    ),
                    TextButton(
                      onPressed: () {
                        Navigator.pushNamed(context, privacyPolicyScreenRoute);
                      },
                      child: const Text('Privacy Policy'),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      );
    }

    final user = _user!;

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 560),
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(defaultPadding),
              child: Column(
                children: [
                  Row(
                    children: [
                      const CircleAvatar(
                        radius: 40,
                        child: Icon(Icons.person, size: 40),
                      ),
                      const SizedBox(width: defaultPadding),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              user.name,
                              style: Theme.of(context).textTheme.titleLarge,
                            ),
                            Text(user.email),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: defaultPadding * 2),
                  ProfileMenu(
                    icon: Icons.person,
                    title: 'My Account',
                    press: () {
                      Navigator.of(context).push(
                        MaterialPageRoute<void>(
                          builder: (context) =>
                              AccountDetailsScreen(user: user),
                        ),
                      );
                    },
                  ),
                  ProfileMenu(
                    icon: Icons.notifications,
                    title: 'Notifications',
                    press: () {
                      Navigator.pushNamed(context, notificationsScreenRoute);
                    },
                  ),
                  ProfileMenu(
                    icon: Icons.settings,
                    title: 'Settings',
                    press: () {
                      Navigator.pushNamed(context, preferencesScreenRoute);
                    },
                  ),
                  ProfileMenu(
                    icon: Icons.privacy_tip_outlined,
                    title: 'Privacy Policy',
                    press: () {
                      Navigator.pushNamed(context, privacyPolicyScreenRoute);
                    },
                  ),
                  ProfileMenu(
                    icon: Icons.help_center,
                    title: 'Help & support',
                    press: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute<void>(
                          builder: (context) => const SupportCenterScreen(),
                        ),
                      );
                    },
                  ),
                  ProfileMenu(
                    icon: Icons.delete_forever_outlined,
                    title: 'Delete account',
                    press: () => _confirmDeleteAccount(user),
                  ),
                  ProfileMenu(
                    icon: Icons.logout,
                    title: 'Logout',
                    press: () async {
                      final navigator = Navigator.of(context);
                      await StorageService.removeUser();
                      if (!mounted) return;
                      navigator.pushNamedAndRemoveUntil(
                        logInScreenRoute,
                        (route) => false,
                      );
                    },
                  ),
                  const SizedBox(height: defaultPadding),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class AccountDetailsScreen extends StatelessWidget {
  const AccountDetailsScreen({super.key, required this.user});

  final UserModel user;

  Widget _detailTile(String label, String value) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      title: Text(label),
      subtitle: Text(value.isEmpty ? '-' : value),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('My Account')),
      body: Padding(
        padding: const EdgeInsets.all(defaultPadding),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _detailTile('Name', user.name),
            const Divider(height: 1),
            _detailTile('Email', user.email),
            const Divider(height: 1),
            _detailTile('User ID', user.id),
          ],
        ),
      ),
    );
  }
}

class ProfileMenu extends StatelessWidget {
  const ProfileMenu({
    super.key,
    required this.icon,
    required this.title,
    required this.press,
  });

  final IconData icon;
  final String title;
  final VoidCallback press;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: defaultPadding / 2),
      child: TextButton(
        style: TextButton.styleFrom(
          padding: const EdgeInsets.all(defaultPadding),
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
          backgroundColor: const Color(0xFFF5F6F9),
        ),
        onPressed: press,
        child: Row(
          children: [
            Icon(icon, size: 22),
            const SizedBox(width: defaultPadding),
            Expanded(child: Text(title)),
            const Icon(Icons.arrow_forward_ios),
          ],
        ),
      ),
    );
  }
}
