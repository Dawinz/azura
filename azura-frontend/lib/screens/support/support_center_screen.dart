import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../constants.dart';

/// In-app support content so users always have help even if the marketing site is down.
/// Keep App Store Connect Support URL pointed at a live page that mirrors this information.
class SupportCenterScreen extends StatelessWidget {
  const SupportCenterScreen({super.key});

  static final Uri _mailto =
      Uri.parse('mailto:support@azuramall.shop?subject=Azuramall%20Support');
  static final Uri _site = Uri.parse('https://azuramall.shop/contact');

  Future<void> _open(Uri uri, ScaffoldMessengerState messenger) async {
    if (!await canLaunchUrl(uri)) {
      messenger.showSnackBar(
        const SnackBar(content: Text('Could not open link')),
      );
      return;
    }
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context) {
    final messenger = ScaffoldMessenger.of(context);
    return Scaffold(
      appBar: AppBar(title: const Text('Help & support')),
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 560),
            child: ListView(
              padding: const EdgeInsets.all(defaultPadding),
              children: [
                Text(
                  'Need help?',
                  style: Theme.of(context).textTheme.headlineSmall,
                ),
                const SizedBox(height: defaultPadding),
                const Text(
                  'Browse and shop without an account. Sign in only when you want '
                  'features tied to your profile, such as saving preferences.',
                ),
                const SizedBox(height: defaultPadding),
                const Text(
                  'You can delete your account anytime from Profile → Delete account '
                  '(you may need to sign in first).',
                ),
                const SizedBox(height: defaultPadding * 2),
                ListTile(
                  leading: const Icon(Icons.email_outlined),
                  title: const Text('Email support'),
                  subtitle: const Text('support@azuramall.shop'),
                  onTap: () => _open(_mailto, messenger),
                ),
                ListTile(
                  leading: const Icon(Icons.language_outlined),
                  title: const Text('Contact page'),
                  subtitle: Text(_site.toString()),
                  onTap: () => _open(_site, messenger),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
