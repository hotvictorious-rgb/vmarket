import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_asset_image_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/controllers/order_details_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/controllers/splash_controller.dart';
import 'package:flutter_sixvalley_ecommerce/utill/images.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:flutter_sixvalley_ecommerce/helper/shop_helper.dart';
import 'package:provider/provider.dart';

class SellerSectionWidget extends StatelessWidget {
  final OrderDetailsController? order;
  const SellerSectionWidget({super.key, this.order});

  @override
  Widget build(BuildContext context) {

    bool isVacationActive = false;

    if (order?.orderDetails != null && order!.orderDetails![0].seller != null) {
      isVacationActive = ShopHelper.isVacationActive(
        context, startDate: order!.orderDetails![0].seller?.shop?.vacationStartDate,
        endDate: order!.orderDetails![0].seller?.shop?.vacationEndDate,
        vacationDurationType: order!.orderDetails![0].seller?.shop?.vacationDurationType,
        vacationStatus: order!.orderDetails![0].seller?.shop?.vacationStatus,
        isInHouseSeller: order!.orderDetails![0].order?.sellerIs == 'admin'
      );
    }

    return Container(
      decoration: BoxDecoration(
        boxShadow: [BoxShadow(color: Theme.of(context).hintColor.withValues(alpha:0.2), spreadRadius:1.5, blurRadius: 3)],
        color: Theme.of(context).cardColor,
      ),

      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
          child: Padding(padding: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeSmall),
            child: Row(children: [
              CustomAssetImageWidget(Images.vendorIcon, color: Theme.of(context).primaryColor, height: 20),
              const SizedBox(width: Dimensions.paddingSizeExtraSmall),
              if( order != null && order!.orderDetails != null && order!.orderDetails != null && order!.orderDetails!.isNotEmpty)
              SizedBox(width: MediaQuery.of(context).size.width * 0.6,
                child: Text(maxLines: 1, overflow: TextOverflow.ellipsis,
                  (order?.orderDetails != null && order!.orderDetails!.isNotEmpty && order!.orderDetails![0].order?.sellerIs == 'admin' ) ? '${Provider.of<SplashController>(context, listen: false).configModel?.inHouseShop?.name}' :
                  '${order?.orderDetails?[0].seller?.shop?.name??'${getTranslated('seller_not_available', context)}'} ',
                  style: textRegular.copyWith(color: Theme.of(context).textTheme.bodyLarge?.color)
                )
              ),
            ]),
          ),
        ),

        Divider(thickness: .25, color: Theme.of(context).primaryColor.withValues(alpha:0.50)),

      ]),
    );
  }
}
