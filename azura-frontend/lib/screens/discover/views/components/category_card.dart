import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

import '../../../../constants.dart';

/// Single-word or ALL-CAPS titles become friendlier (e.g. `SHOES` → `Shoes`).
String displayCategoryTitle(String raw) {
  final t = raw.trim();
  if (t.isEmpty) return t;
  if (t.length > 1 && t == t.toUpperCase() && RegExp(r'[A-Z]').hasMatch(t)) {
    return t
        .split(RegExp(r'\s+'))
        .map((w) {
          if (w.isEmpty) return w;
          return '${w[0].toUpperCase()}${w.substring(1).toLowerCase()}';
        })
        .join(' ');
  }
  return t;
}

/// Slug under API → readable caption (`fashion-demo` → `Fashion demo`).
String prettifyCategorySlug(String slug) {
  final s = slug.trim();
  if (s.isEmpty) return s;
  return s
      .replaceAll('-', ' ')
      .replaceAll('_', ' ')
      .split(RegExp(r'\s+'))
      .map((w) {
        if (w.isEmpty) return w;
        return '${w[0].toUpperCase()}${w.substring(1).toLowerCase()}';
      })
      .join(' ');
}

class CategoryCard extends StatelessWidget {
  const CategoryCard({
    super.key,
    required this.title,
    required this.subtitle,
    this.imageUrl,
    required this.press,
  });

  final String title;
  final String subtitle;
  final String? imageUrl;
  final VoidCallback press;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final displayTitle = displayCategoryTitle(title);
    final caption = subtitle.isNotEmpty ? prettifyCategorySlug(subtitle) : '';

    return Material(
      color: scheme.surfaceContainerHighest.withValues(alpha: 0.35),
      borderRadius: BorderRadius.circular(defaultPadding),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: press,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Expanded(
              flex: 5,
              child: imageUrl != null && imageUrl!.isNotEmpty
                  ? CachedNetworkImage(
                      imageUrl: imageUrl!,
                      fit: BoxFit.cover,
                      placeholder: (_, __) => ColoredBox(
                        color: scheme.surfaceContainerHighest,
                        child: const Center(
                          child: SizedBox(
                            width: 28,
                            height: 28,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          ),
                        ),
                      ),
                      errorWidget: (_, __, ___) => _CategoryPlaceholder(scheme: scheme),
                    )
                  : _CategoryPlaceholder(scheme: scheme),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 10, 12, 12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    displayTitle,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: theme.textTheme.titleSmall?.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  if (caption.isNotEmpty &&
                      caption.toLowerCase() != displayTitle.toLowerCase()) ...[
                    const SizedBox(height: 4),
                    Text(
                      caption,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: scheme.onSurfaceVariant,
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _CategoryPlaceholder extends StatelessWidget {
  const _CategoryPlaceholder({required this.scheme});

  final ColorScheme scheme;

  @override
  Widget build(BuildContext context) {
    return ColoredBox(
      color: scheme.surfaceContainerHighest,
      child: Center(
        child: Icon(
          Icons.category_outlined,
          size: 40,
          color: scheme.onSurfaceVariant.withValues(alpha: 0.5),
        ),
      ),
    );
  }
}
