import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/controllers/seller_product_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/shop/domain/models/shop_navigation_model.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/title_row_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/shop/widgets/shop_featured_product_list_view.dart';
import 'package:flutter_sixvalley_ecommerce/features/shop/widgets/shop_recommanded_product_list.dart';
import 'package:provider/provider.dart';

class ShopOverviewScreen extends StatefulWidget {
  final String slug;
  final ScrollController scrollController;
  final SellerNavigationModel? sellerNavigationModel;
  const ShopOverviewScreen({super.key, required this.slug, required this.scrollController, this.sellerNavigationModel});

  @override
  State<ShopOverviewScreen> createState() => _ShopOverviewScreenState();
}

class _ShopOverviewScreenState extends State<ShopOverviewScreen> {
  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        const SizedBox(height: Dimensions.paddingSizeSmall),

        Consumer<SellerProductController>(
          builder: (context, productController, _) {
            return TitleRowWidget(
              title: productController.sellerWiseFeaturedProduct != null ?
                (productController.sellerWiseFeaturedProduct!.products != null &&
                  productController.sellerWiseFeaturedProduct!.products!.isNotEmpty) ?
                getTranslated('featured_products', context) : getTranslated('recommanded_products', context) : '',
            );
          },
        ),

        Consumer<SellerProductController>(
          builder: (context, productController, _) {
            return Padding(
              padding: const EdgeInsets.fromLTRB(
                Dimensions.paddingSizeSmall,
                Dimensions.paddingSizeDefault,
                Dimensions.paddingSizeSmall,
                0,
              ),
              child: ShopFeaturedProductViewList(
                scrollController: widget.scrollController,
                slug: widget.slug,
                sellerNavigationModel: widget.sellerNavigationModel,
              ),
            );
          },
        ),

        Consumer<SellerProductController>(
          builder: (context, productController, _) {
            return (productController.sellerWiseFeaturedProduct != null &&
              productController.sellerWiseFeaturedProduct!.products != null &&
              productController.sellerWiseFeaturedProduct!.products!.isEmpty) ?
              Padding(
                padding: const EdgeInsets.fromLTRB(
                  Dimensions.paddingSizeSmall,
                  Dimensions.paddingSizeDefault,
                  Dimensions.paddingSizeSmall,
                  0,
                ),
                child: ShopRecommandedProductViewList(
                  scrollController: widget.scrollController,
                  sellerNavigationModel: widget.sellerNavigationModel,
                  slug: widget.slug,
                ),
              ) : const SizedBox();
          },
        ),
      ],
    );
  }
}
