import 'package:flutter/material.dart';
import 'package:shop/constants.dart';

class SizeGuideScreen extends StatelessWidget {
  const SizeGuideScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(title: const Text('Size guide')),
      body: ListView(
        padding: const EdgeInsets.all(defaultPadding),
        children: [
          Text(
            'Sizing varies by seller and brand. For the best fit, check the product description and photos, or message the seller before you buy.',
            style: theme.textTheme.bodyLarge?.copyWith(height: 1.5),
          ),
          const SizedBox(height: defaultPadding),
          Text(
            'Tips',
            style: theme.textTheme.titleSmall,
          ),
          const SizedBox(height: 8),
          const Text('• Compare measurements to a garment you already own.'),
          const Text('• Ask the seller for a size chart if none is listed.'),
          const Text('• Remember: local tailors can often adjust hems and sleeves.'),
        ],
      ),
    );
  }
}
