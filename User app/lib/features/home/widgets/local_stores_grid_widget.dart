import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/title_row_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/location/controllers/location_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/shop/controllers/shop_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/shop/domain/models/seller_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/shop/widgets/seller_card.dart';
import 'package:flutter_sixvalley_ecommerce/helper/responsive_helper.dart';
import 'package:flutter_sixvalley_ecommerce/helper/route_healper.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:provider/provider.dart';

class LocalStoresGridWidget extends StatelessWidget {
  const LocalStoresGridWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return Consumer<LocationController>(
      builder: (context, locCtrl, _) {
        final activeCity = locCtrl.activeLgaName;

        return Consumer<ShopController>(
          builder: (context, shopController, _) {
            final sellerModel = shopController.topSellerModel;
            final sellers = sellerModel?.sellers;

            if (sellers == null || sellers.isEmpty) {
              return const SizedBox();
            }

            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                TitleRowWidget(
                  title: 'Stores Closer to You in $activeCity',
                  onTap: () => RouterHelper.getAllTopSellerRoute(
                    action: RouteAction.push,
                    title: 'Stores in $activeCity',
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault, vertical: 4),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFFFBEB),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: const Color(0xFFFDE68A)),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.storefront_outlined, size: 12, color: Color(0xFFB45309)),
                            const SizedBox(width: 4),
                            Text(
                              'Verified Local Merchants & Pickup Points',
                              style: textRegular.copyWith(
                                fontSize: 10.5,
                                color: const Color(0xFF92400E),
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: Dimensions.paddingSizeSmall),
                SizedBox(
                  height: ResponsiveHelper.isTab(context) ? 180 : 165,
                  child: ListView.builder(
                    itemCount: sellers.length,
                    padding: EdgeInsets.zero,
                    scrollDirection: Axis.horizontal,
                    physics: const BouncingScrollPhysics(),
                    itemBuilder: (BuildContext context, int index) => SizedBox(
                      width: MediaQuery.of(context).size.width * .70,
                      child: SellerCard(
                        sellerModel: sellers[index],
                        isHomePage: true,
                        index: index,
                        length: sellers.length,
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: Dimensions.paddingSizeDefault),
              ],
            );
          },
        );
      },
    );
  }
}
