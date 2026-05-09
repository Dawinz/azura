import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

import '../../../constants.dart';
import '../../models/order_model.dart';

class OrderCard extends StatelessWidget {
  const OrderCard({
    super.key,
    required this.order,
    required this.press,
  });

  final OrderModel order;
  final VoidCallback press;

  @override
  Widget build(BuildContext context) {
    const Color textColor = Colors.black54;

    return GestureDetector(
      onTap: press,
      child: Container(
        padding: const EdgeInsets.all(defaultPadding),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius:
              const BorderRadius.all(Radius.circular(defaultBorderRadious)),
          border: Border.all(color: Colors.black12),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                SvgPicture.asset(
                  "assets/icons/Parcel.svg",
                  colorFilter:
                      const ColorFilter.mode(primaryColor, BlendMode.srcIn),
                ),
                const SizedBox(width: defaultPadding / 2),
                Text(
                  order.id,
                  style: const TextStyle(
                      fontWeight: FontWeight.w500, fontSize: 12),
                ),
                const Spacer(),
                Text(
                  "\$${order.total.toStringAsFixed(2)}",
                  style: Theme.of(context).textTheme.titleSmall,
                )
              ],
            ),
            const Padding(
              padding: EdgeInsets.symmetric(vertical: defaultPadding / 2),
              child: Divider(height: 1),
            ),
            Text.rich(
              TextSpan(
                text: "Product: ",
                style: const TextStyle(color: textColor),
                children: [
                  TextSpan(
                    text: order.items.isNotEmpty
                        ? order.items[0].product.title
                        : '',
                    style: const TextStyle(
                        fontWeight: FontWeight.w500, color: Colors.black87),
                  )
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(vertical: defaultPadding / 4),
              child: Text.rich(
                TextSpan(
                  text: "Quantiy: ",
                  style: const TextStyle(color: textColor),
                  children: [
                    TextSpan(
                      text: order.items.isNotEmpty
                          ? order.items[0].quantity.toString()
                          : "",
                      style: const TextStyle(
                          fontWeight: FontWeight.w500, color: Colors.black87),
                    )
                  ],
                ),
              ),
            ),
            Text.rich(
              TextSpan(
                text: "Order Date: ",
                style: const TextStyle(color: textColor),
                children: [
                  TextSpan(
                    text: order.date,
                    style: const TextStyle(
                        fontWeight: FontWeight.w500, color: Colors.black87),
                  )
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(vertical: defaultPadding / 4),
              child: Text.rich(
                TextSpan(
                  text: "Order Status: ",
                  style: const TextStyle(color: textColor),
                  children: [
                    TextSpan(
                      text: order.status,
                      style: const TextStyle(
                          fontWeight: FontWeight.w500, color: Colors.black87),
                    )
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
