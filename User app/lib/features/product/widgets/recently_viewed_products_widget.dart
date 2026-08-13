import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/product_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/controllers/product_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/domain/models/product_model.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:provider/provider.dart';

class RecentlyViewedProductsWidget extends StatelessWidget {
  final int? currentProductId;
  const RecentlyViewedProductsWidget({super.key, this.currentProductId});

  @override
  Widget build(BuildContext context) {
    return Consumer<ProductController>(
      builder: (context, productController, _) {
        final List<Product> list = productController.recentlyViewedProducts
            .where((p) => p.id != currentProductId)
            .toList();

        if (list.isEmpty) {
          return const SizedBox.shrink();
        }

        return Container(
          margin: const EdgeInsets.only(top: Dimensions.paddingSizeSmall),
          padding: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeSmall),
          color: Theme.of(context).cardColor,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                child: Text(
                  getTranslated('recently_viewed', context) ?? 'Recently Viewed',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                    fontSize: Dimensions.fontSizeLarge,
                    color: Theme.of(context).textTheme.bodyLarge?.color,
                  ),
                ),
              ),
              const SizedBox(height: Dimensions.paddingSizeSmall),

              SizedBox(
                height: 250,
                child: ListView.builder(
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeSmall),
                  itemCount: list.length,
                  itemBuilder: (context, index) {
                    return SizedBox(
                      width: 160,
                      child: ProductWidget(productModel: list[index]),
                    );
                  },
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}
