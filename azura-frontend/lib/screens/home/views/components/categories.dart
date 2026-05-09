import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:shop/route/screen_export.dart';
import 'package:shop/models/category_model.dart' as api;

import '../../../../constants.dart';

// For preview
class CategoryModel {
  final String name;
  final String? svgSrc, route;

  CategoryModel({
    required this.name,
    this.svgSrc,
    this.route,
  });
}

List<CategoryModel> demoCategories = [
  CategoryModel(name: "All Categories"),
  CategoryModel(
      name: "On Sale",
      svgSrc: "assets/icons/Sale.svg",
      route: onSaleScreenRoute),
  CategoryModel(name: "Man's", svgSrc: "assets/icons/Man.svg"),
  CategoryModel(name: "Woman’s", svgSrc: "assets/icons/Woman.svg"),
  CategoryModel(
      name: "Kids", svgSrc: "assets/icons/Child.svg", route: kidsScreenRoute),
];
// End For Preview

class Categories extends StatelessWidget {
  const Categories({
    super.key,
    this.categories,
  });

  final List<api.CategoryModel>? categories;

  @override
  Widget build(BuildContext context) {
    final bool useApiCategories = categories != null && categories!.isNotEmpty;
    final int itemCount =
        useApiCategories ? categories!.length : demoCategories.length;
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: [
          ...List.generate(
            itemCount,
            (index) => Padding(
              padding: EdgeInsets.only(
                  left: index == 0 ? defaultPadding : defaultPadding / 2,
                  right: index == itemCount - 1 ? defaultPadding : 0),
              child: CategoryBtn(
                category: useApiCategories
                    ? categories![index].title
                    : demoCategories[index].name,
                svgSrc: useApiCategories ? null : demoCategories[index].svgSrc,
                isActive: index == 0,
                press: () {
                  if (useApiCategories) {
                    final c = categories![index];
                    if (c.id > 0) {
                      Navigator.of(context).push(
                        MaterialPageRoute<void>(
                          builder: (context) => ProductListScreen(
                            categoryId: c.id,
                            title: c.title,
                          ),
                        ),
                      );
                    }
                  } else if (demoCategories[index].route != null) {
                    Navigator.pushNamed(context, demoCategories[index].route!);
                  }
                },
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class CategoryBtn extends StatelessWidget {
  const CategoryBtn({
    super.key,
    required this.category,
    this.svgSrc,
    required this.isActive,
    required this.press,
  });

  final String category;
  final String? svgSrc;
  final bool isActive;
  final VoidCallback press;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: press,
      borderRadius: const BorderRadius.all(Radius.circular(30)),
      child: Container(
        height: 36,
        padding: const EdgeInsets.symmetric(horizontal: defaultPadding),
        decoration: BoxDecoration(
          color: isActive ? primaryColor : Colors.transparent,
          border: Border.all(
              color: isActive
                  ? Colors.transparent
                  : Theme.of(context).dividerColor),
          borderRadius: const BorderRadius.all(Radius.circular(30)),
        ),
        child: Row(
          children: [
            if (svgSrc != null)
              SvgPicture.asset(
                svgSrc!,
                height: 20,
                colorFilter: ColorFilter.mode(
                  isActive ? Colors.white : Theme.of(context).iconTheme.color!,
                  BlendMode.srcIn,
                ),
              ),
            if (svgSrc != null) const SizedBox(width: defaultPadding / 2),
            Text(
              category,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w500,
                color: isActive
                    ? Colors.white
                    : Theme.of(context).textTheme.bodyLarge!.color,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
