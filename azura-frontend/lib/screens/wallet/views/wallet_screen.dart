import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/constants.dart';
import 'package:shop/models/product_model.dart';

import 'components/wallet_balance_card.dart';
import 'components/wallet_history_card.dart';

class WalletScreen extends StatelessWidget {
  const WalletScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text("Wallet"),
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: defaultPadding),
          child: Column(
            children: [
              WalletBalanceCard(
                balance: 384.90,
                onTabChargeBalance: () {},
              ),
              const SizedBox(height: defaultPadding * 2),
              Text(
                "Wallet history",
                style: Theme.of(context).textTheme.titleSmall,
              ),
              Expanded(
                child: FutureBuilder<List<ProductModel>>(
                  future: ApiService.getProducts(),
                  builder: (context, snapshot) {
                    if (snapshot.hasData) {
                      return ListView.builder(
                        itemCount: snapshot.data!.length,
                        itemBuilder: (context, index) => Padding(
                          padding: const EdgeInsets.only(top: defaultPadding),
                          child: WalletHistoryCard(
                            isReturn: index == 1,
                            date: "JUN 12, 2020",
                            amount: 129,
                            products: snapshot.data!,
                          ),
                        ),
                      );
                    } else if (snapshot.hasError) {
                      return Center(
                        child: Text(snapshot.error.toString()),
                      );
                    }
                    return const Center(
                      child: CircularProgressIndicator(),
                    );
                  },
                ),
              )
            ],
          ),
        ),
      ),
    );
  }
}
