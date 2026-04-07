import 'package:flutter/material.dart';
import 'package:shop/route/route_constants.dart';

import '../../../constants.dart';

class PrivacyPolicyScreen extends StatelessWidget {
  const PrivacyPolicyScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text("Privacy Policy"),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(defaultPadding),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _Section(
              title: "Introduction",
              body:
                  "Azura Mall (“we”, “our”, or “us”) is committed to protecting your privacy. "
                  "This Privacy Policy explains how we collect, use, disclose, and safeguard your "
                  "information when you use our mobile and web applications and services.",
            ),
            _Section(
              title: "Information We Collect",
              body:
                  "We may collect information you provide directly (such as name, email, phone, "
                  "shipping address, and payment information), information from your device (such as "
                  "device type and identifiers), and usage data (such as pages visited and actions taken) "
                  "to operate and improve our services.",
            ),
            _Section(
              title: "How We Use Your Information",
              body:
                  "We use your information to process orders, communicate with you, personalize your "
                  "experience, improve our app and services, prevent fraud, and comply with legal "
                  "obligations. We may also use it for marketing with your consent.",
            ),
            _Section(
              title: "Sharing of Information",
              body:
                  "We do not sell your personal information. We may share information with service "
                  "providers (e.g. payment processors, delivery partners), when required by law, or "
                  "to protect our rights and safety.",
            ),
            _Section(
              title: "Data Security",
              body:
                  "We implement appropriate technical and organizational measures to protect your "
                  "personal data. No method of transmission over the internet is 100% secure; we "
                  "strive to use commercially acceptable means to protect your data.",
            ),
            _Section(
              title: "Your Rights",
              body:
                  "Depending on your location, you may have the right to access, correct, delete, or "
                  "port your data, and to object to or restrict certain processing. Contact us to "
                  "exercise these rights.",
            ),
            _Section(
              title: "Cookies and Similar Technologies",
              body:
                  "Our web app may use cookies and similar technologies for essential functionality, "
                  "analytics, and preferences. You can manage cookie settings in your browser.",
            ),
            _Section(
              title: "Children",
              body:
                  "Our services are not directed to individuals under the age of 13. We do not "
                  "knowingly collect personal information from children under 13.",
            ),
            _Section(
              title: "Changes to This Policy",
              body:
                  "We may update this Privacy Policy from time to time. We will notify you of "
                  "material changes by posting the updated policy in the app or by email where "
                  "appropriate. Your continued use after changes constitutes acceptance.",
            ),
            _Section(
              title: "Contact Us",
              body:
                  "If you have questions about this Privacy Policy or your personal data, please "
                  "contact us through the app’s Help Center or the contact details provided in "
                  "the app or on our website.",
            ),
            const SizedBox(height: defaultPadding * 2),
          ],
        ),
      ),
    );
  }
}

class _Section extends StatelessWidget {
  const _Section({required this.title, required this.body});

  final String title;
  final String body;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: defaultPadding * 1.5),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                  color: primaryColor,
                ),
          ),
          const SizedBox(height: defaultPadding / 2),
          Text(
            body,
            style: Theme.of(context).textTheme.bodyMedium,
          ),
        ],
      ),
    );
  }
}
