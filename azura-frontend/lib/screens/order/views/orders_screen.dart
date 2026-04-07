import 'package:flutter/material.dart';
import 'package:shop/components/order_card.dart';
import 'package:shop/constants.dart';
import 'package:shop/models/order_model.dart';
import 'package:shop/models/product_model.dart';

// This is a demo order list. You need to replace it with your own data.
final List<OrderModel> demoOrders = [
  OrderModel(
    id: "#343ff",
    date: "12/12/2022",
    items: [
      OrderItemModel(
        product: ProductModel(
          id: '1',
          slug: "t-shirt",
          title: "T-shirt",
          price: 20,
          image: "https://i.imgur.com/8JCRzZT.jpeg",
          files: [],
          productType: '',
          listingType: '',
          categoryId: '',
          currency: '',
          userId: '',
          status: '',
          isPromoted: '',
          promoteStartDate: '',
          promoteEndDate: '',
          promotePlan: '',
          promoteDay: '',
          visibility: '',
          rating: '',
          externalLink: '',
          filesIncluded: '',
          shippingTime: '',
          isSold: '',
          isDeleted: '',
          isDraft: '',
          createdAt: '',
          userUsername: '',
          shopName: '',
          userRole: '',
          userSlug: '',
          productUrl: '',
        ),
        quantity: 2,
      ),
    ],
    total: 40,
    status: "Processing",
  )
];

class OrdersScreen extends StatelessWidget {
  const OrdersScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text("My Orders"),
      ),
      body: ListView.separated(
        padding: const EdgeInsets.all(defaultPadding),
        itemCount: demoOrders.length,
        separatorBuilder: (context, index) =>
            const SizedBox(height: defaultPadding),
        itemBuilder: (context, index) {
          return OrderCard(
            order: demoOrders[index],
            press: () {},
          );
        },
      ),
    );
  }
}
