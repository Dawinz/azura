import 'package:flutter/material.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/components/product/secondary_product_card.dart';
import 'package:shop/models/product_model.dart';
import 'package:shop/screens/details/details_screen.dart';

import '../../../../constants.dart';

class MostPopular extends StatefulWidget {
  const MostPopular({super.key});

  @override
  State<MostPopular> createState() => _MostPopularState();
}

class _MostPopularState extends State<MostPopular> {
  late Future<List<ProductModel>> _mostPopularProductsFuture;

  @override
  void initState() {
    super.initState();
    _mostPopularProductsFuture = ApiService.getMostPopularProducts();
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.all(defaultPadding),
          child: Text("Most popular"),
        ),
        const SizedBox(height: defaultPadding),
        SizedBox(
          height: 250,
          child: FutureBuilder<List<ProductModel>>(
            future: _mostPopularProductsFuture,
            builder: (context, snapshot) {
              if (snapshot.hasError) {
                return Center(
                  child: Text(snapshot.error.toString()),
                );
              }
              if (snapshot.hasData) {
                return ListView.builder(
                  scrollDirection: Axis.horizontal,
                  itemCount: snapshot.data!.length,
                  itemBuilder: (context, index) => Padding(
                    padding: EdgeInsets.only(
                        left: defaultPadding,
                        right: index == snapshot.data!.length - 1
                            ? defaultPadding
                            : 0),
                    child: SecondaryProductCard(
                      product: snapshot.data![index],
                      press: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (context) => DetailsScreen(
                              product: snapshot.data![index],
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                );
              }
              return const Center(child: CircularProgressIndicator());
            },
          ),
        ),
      ],
    );
  }
}
