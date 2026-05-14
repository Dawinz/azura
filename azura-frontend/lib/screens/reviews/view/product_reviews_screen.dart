import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/constants.dart';
import 'package:shop/models/product_model.dart';
import 'package:shop/services/storage_service.dart';

class ProductReviewsScreen extends StatefulWidget {
  const ProductReviewsScreen({super.key, this.product});

  final ProductModel? product;

  @override
  State<ProductReviewsScreen> createState() => _ProductReviewsScreenState();
}

class _ProductReviewsScreenState extends State<ProductReviewsScreen> {
  final _text = TextEditingController();
  int _rating = 5;
  bool _submitting = false;
  late Future<List<ProductReviewItem>> _reviewsFuture;

  @override
  void initState() {
    super.initState();
    _reviewsFuture = _fetchReviews();
  }

  Future<List<ProductReviewItem>> _fetchReviews() {
    final pid = int.tryParse(widget.product?.id ?? '') ?? 0;
    return ApiService.getProductReviews(pid);
  }

  Future<void> _reloadReviews() async {
    setState(() => _reviewsFuture = _fetchReviews());
    await _reviewsFuture;
  }

  @override
  void dispose() {
    _text.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final p = widget.product;
    if (p == null) return;
    final user = await StorageService.getUser();
    final uid = int.tryParse(user?.id ?? '') ?? 0;
    if (uid < 1) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please sign in to leave a review.')),
      );
      return;
    }
    final pid = int.tryParse(p.id) ?? 0;
    if (pid < 1) return;
    setState(() => _submitting = true);
    try {
      await ApiService.addReview(uid, pid, _rating, _text.text.trim());
      if (!mounted) return;
      _text.clear();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Thanks — your review was submitted.')),
      );
      await _reloadReviews();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString())),
      );
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final p = widget.product;
    if (p == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Reviews')),
        body: const Center(
          child: Padding(
            padding: EdgeInsets.all(24),
            child: Text(
              'Open a product, then tap Reviews from the product page.',
              textAlign: TextAlign.center,
            ),
          ),
        ),
      );
    }
    return Scaffold(
      appBar: AppBar(title: Text('Reviews · ${p.title}')),
      body: RefreshIndicator(
        onRefresh: _reloadReviews,
        child: FutureBuilder<List<ProductReviewItem>>(
          future: _reviewsFuture,
          builder: (context, snap) {
            final list = snap.data ?? [];
            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(defaultPadding),
              children: [
                Text(
                  'Write a review',
                  style: theme.textTheme.titleSmall,
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    const Text('Rating'),
                    const SizedBox(width: 12),
                    DropdownButton<int>(
                      value: _rating,
                      items: List.generate(
                        5,
                        (i) => DropdownMenuItem(
                          value: i + 1,
                          child: Text('${i + 1} stars'),
                        ),
                      ),
                      onChanged: (v) {
                        if (v != null) setState(() => _rating = v);
                      },
                    ),
                  ],
                ),
                TextField(
                  controller: _text,
                  maxLines: 4,
                  decoration: const InputDecoration(
                    labelText: 'Your review',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 12),
                FilledButton(
                  onPressed: _submitting ? null : _submit,
                  child: _submitting
                      ? const SizedBox(
                          width: 22,
                          height: 22,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Text('Submit review'),
                ),
                const Divider(height: defaultPadding * 2),
                Text(
                  'What buyers said',
                  style: theme.textTheme.titleSmall,
                ),
                const SizedBox(height: 8),
                if (snap.connectionState == ConnectionState.waiting)
                  const Padding(
                    padding: EdgeInsets.all(24),
                    child: Center(child: CircularProgressIndicator()),
                  )
                else if (list.isEmpty)
                  Text(
                    'No reviews yet. Be the first to leave one.',
                    style: theme.textTheme.bodyMedium,
                  )
                else
                  ...list.map(
                    (r) => Card(
                      margin: const EdgeInsets.only(bottom: 12),
                      child: ListTile(
                        title: Text(
                          r.username.isNotEmpty ? r.username : 'Buyer',
                        ),
                        subtitle: Text(r.review),
                        trailing: Text('${r.rating}★'),
                      ),
                    ),
                  ),
                const SizedBox(height: 48),
              ],
            );
          },
        ),
      ),
    );
  }
}
