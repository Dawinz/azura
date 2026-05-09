import 'package:flutter/material.dart';

/// Placeholder for screens that were bundled as marketing previews in the original template.
/// External checkout links were removed to comply with App Store guideline 3.1.1 (In-App Purchase).
class BuyFullKit extends StatelessWidget {
  const BuyFullKit({super.key, required this.images});

  final List<String> images;

  @override
  Widget build(BuildContext context) {
    if (images.isEmpty) {
      return Scaffold(
        appBar: AppBar(title: const Text('Azuramall')),
        body: const Center(child: Text('This section is not available.')),
      );
    }
    return Scaffold(
      appBar: AppBar(title: const Text('Azuramall')),
      body: SafeArea(
        child: PageView.builder(
          itemCount: images.length,
          itemBuilder: (context, index) {
            return Padding(
              padding: const EdgeInsets.all(16),
              child: Image.asset(
                images[index],
                fit: BoxFit.contain,
              ),
            );
          },
        ),
      ),
    );
  }
}
