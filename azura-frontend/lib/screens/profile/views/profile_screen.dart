import 'package:flutter/material.dart';
import 'package:shop/models/user_model.dart';
import 'package:shop/route/route_constants.dart';
import 'package:shop/services/storage_service.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../constants.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  UserModel? _user;

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
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text("Profile"),
      ),
      body: _user == null
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
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
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _user!.name,
                            style: Theme.of(context).textTheme.titleLarge,
                          ),
                          Text(_user!.email),
                        ],
                      )
                    ],
                  ),
                  const SizedBox(height: defaultPadding * 2),
                  ProfileMenu(
                      icon: Icons.person,
                      title: "My Account",
                      press: () {
                        Navigator.of(context).push(
                          MaterialPageRoute<void>(
                            builder: (context) =>
                                AccountDetailsScreen(user: _user!),
                          ),
                        );
                      }),
                  ProfileMenu(
                      icon: Icons.notifications,
                      title: "Notifications",
                      press: () {
                        Navigator.pushNamed(context, notificationsScreenRoute);
                      }),
                  ProfileMenu(
                      icon: Icons.settings,
                      title: "Settings",
                      press: () {
                        Navigator.pushNamed(context, preferencesScreenRoute);
                      }),
                  ProfileMenu(
                      icon: Icons.privacy_tip_outlined,
                      title: "Privacy Policy",
                      press: () {
                        Navigator.pushNamed(context, privacyPolicyScreenRoute);
                      }),
                  ProfileMenu(
                      icon: Icons.help_center,
                      title: "Help Center",
                      press: () async {
                        final messenger = ScaffoldMessenger.of(context);
                        final uri = Uri.parse('https://azuramall.shop/contact');
                        if (!await canLaunchUrl(uri)) {
                          if (!mounted) return;
                          messenger.showSnackBar(
                            const SnackBar(
                              content: Text('Could not open help link'),
                            ),
                          );
                          return;
                        }
                        await launchUrl(uri,
                            mode: LaunchMode.externalApplication);
                      }),
                  ProfileMenu(
                      icon: Icons.logout,
                      title: "Logout",
                      press: () async {
                        final navigator = Navigator.of(context);
                        await StorageService.removeUser();
                        if (!mounted) return;
                        navigator.pushNamedAndRemoveUntil(
                          logInScreenRoute,
                          (route) => false,
                        );
                      }),
                  const SizedBox(height: defaultPadding),
                ],
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
