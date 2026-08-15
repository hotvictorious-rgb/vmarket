import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/common/controllers/localization_controller.dart';
import 'package:sixvalley_delivery_boy/features/order_details/widgets/cal_chat_widget.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/models/order_model.dart';
import 'package:sixvalley_delivery_boy/utill/app_constants.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_image_widget.dart';

class SellerInfoWidget extends StatelessWidget {
  final OrderModel? orderModel;

  const SellerInfoWidget({Key? key, this.orderModel}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return  Container(padding:  EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeSmall,vertical: Dimensions.paddingSizeDefault),
      decoration: BoxDecoration(color: Theme.of(context).cardColor,
          boxShadow: [BoxShadow(color: Theme.of(context).primaryColor.withValues(alpha:.05),
              blurRadius: 5, spreadRadius: 1)],
          borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall)),
      child: Column(children: [

          orderModel!.sellerInfo != null?
          Row(crossAxisAlignment: CrossAxisAlignment.center, children: [
            Container(
                decoration: BoxDecoration(
                  color: Theme.of(context).cardColor.withValues(alpha:.25),
                  border: Border.all(color: Theme.of(context).primaryColor, width: 1),
                  borderRadius: BorderRadius.circular(100),
                ),
                child: ClipRRect(
                    borderRadius: BorderRadius.circular(100),
                    child: CustomImageWidget(
                        image: '${orderModel?.sellerInfo?.shop?.imageFullUrl?.path}',
                        height: 40,
                        width: 40,
                        fit: BoxFit.cover,
                    ),
                ),
            ),


              SizedBox(width: Get.find<LocalizationController>().isLtr? 0 : Dimensions.paddingSizeSmall),
              Expanded(child: Padding(padding:  EdgeInsets.fromLTRB(Dimensions.paddingSizeSmall,0,0,0),
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(
                        orderModel!.sellerIs == 'admin'
                            ? AppConstants.companyName
                            : orderModel?.sellerInfo?.shop?.name ?? '',
                        style: rubikMedium.copyWith(
                            fontSize: Dimensions.fontSizeSmall,
                            color: Get.isDarkMode ? Theme.of(context).hintColor : Colors.black,
                        ),
                      ),
                      SizedBox(height: Dimensions.paddingSizeExtraSmall),

                      Text(orderModel!.sellerIs == 'admin' ? 'admin'.tr : 'seller'.tr, style: rubikRegular.copyWith(
                              fontSize: Dimensions.fontSizeExtraSmall,
                              color: Get.isDarkMode
                                  ? Theme.of(context).hintColor
                                  : Theme.of(context).primaryColor.withValues(alpha:.75)
                          )),
                  ]),
              )),

              CallAndChatWidget(orderModel: orderModel, isSeller: true, isAdmin: orderModel!.sellerIs == 'admin',),
            ],
          ):const SizedBox(),

        ],
      ),
    );
  }
}
