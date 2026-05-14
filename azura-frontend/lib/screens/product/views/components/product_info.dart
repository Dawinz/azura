import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:shop/core/market_format.dart';

import '../../../../constants.dart';
import 'product_availability_tag.dart';

class ProductInfo extends StatelessWidget {
  const ProductInfo({
    super.key,
    required this.title,
    required this.brand,
    this.description,
    required this.rating,
    required this.numOfReviews,
    required this.isAvailable,
    this.currency,
    this.productCondition,
    this.price,
    this.onReviewsTap,
  });

  final String title, brand;
  final String? description;
  final double rating;
  final int numOfReviews;
  final bool isAvailable;
  final String? currency;
  final String? productCondition;
  final double? price;
  final VoidCallback? onReviewsTap;

  @override
  Widget build(BuildContext context) {
    return SliverPadding(
      padding: const EdgeInsets.all(defaultPadding),
      sliver: SliverToBoxAdapter(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              brand.toUpperCase(),
              style: const TextStyle(fontWeight: FontWeight.w500),
            ),
            const SizedBox(height: defaultPadding / 2),
            Text(
              title,
              maxLines: 2,
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: defaultPadding),
            if (price != null) ...[
              Text(
                MarketFormat.formatAmount(price!),
                style: Theme.of(context).textTheme.titleLarge,
              ),
              const SizedBox(height: defaultPadding),
            ],
            Row(
              children: [
                ProductAvailabilityTag(isAvailable: isAvailable),
                const Spacer(),
                InkWell(
                  onTap: onReviewsTap,
                  borderRadius: BorderRadius.circular(8),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(
                      vertical: 4,
                      horizontal: 4,
                    ),
                    child: Row(
                      children: [
                        SvgPicture.asset('assets/icons/Star_filled.svg'),
                        const SizedBox(width: defaultPadding / 4),
                        Text(
                          '$rating ',
                          style: Theme.of(context).textTheme.bodyLarge,
                        ),
                        Text(
                          '($numOfReviews)',
                          style: Theme.of(context).textTheme.bodyLarge,
                        ),
                        if (onReviewsTap != null) ...[
                          const SizedBox(width: 4),
                          Icon(
                            Icons.reviews_outlined,
                            size: 18,
                            color: Theme.of(context).colorScheme.primary,
                          ),
                        ],
                      ],
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: defaultPadding),
            if (description != null) ...[
              Text(
                "Product info",
                style: Theme.of(context)
                    .textTheme
                    .titleMedium!
                    .copyWith(fontWeight: FontWeight.w500),
              ),
              const SizedBox(height: defaultPadding / 2),
              Text(
                description!,
                style: const TextStyle(height: 1.4),
              ),
              const SizedBox(height: defaultPadding / 2),
            ],
            if (productCondition != null) ...[
              Text(
                "Product condition",
                style: Theme.of(context)
                    .textTheme
                    .titleMedium!
                    .copyWith(fontWeight: FontWeight.w500),
              ),
              const SizedBox(height: defaultPadding / 2),
              Text(
                productCondition!,
                style: const TextStyle(height: 1.4),
              ),
            ]
          ],
        ),
      ),
    );
  }
}
