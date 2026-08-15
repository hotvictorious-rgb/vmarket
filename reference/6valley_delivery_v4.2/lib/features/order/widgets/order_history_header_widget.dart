import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:sixvalley_delivery_boy/features/order/controllers/order_controller.dart';
import 'package:sixvalley_delivery_boy/helper/color_helper.dart';
import 'package:sixvalley_delivery_boy/helper/debounce_helper.dart';
import 'package:sixvalley_delivery_boy/theme/controllers/theme_controller.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_search_widget.dart';
import 'package:sixvalley_delivery_boy/features/order/widgets/order_type_button_widget.dart';
import 'package:sixvalley_delivery_boy/features/wallet/widgets/transaction_search_filter_widget.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';


class OrderHistoryHeaderWidget extends StatefulWidget {
  const OrderHistoryHeaderWidget({Key? key}) : super(key: key);

  @override
  State<OrderHistoryHeaderWidget> createState() => _OrderHistoryHeaderWidgetState();
}

class _OrderHistoryHeaderWidgetState extends State<OrderHistoryHeaderWidget> {

  final OrderController orderController = Get.find<OrderController>();
  final DebounceHelper debounceHelper = DebounceHelper(milliseconds: 500);

  @override
  void initState() {
    orderController.setSearchText(searchText: '', isUpdate: false);
    super.initState();
  }


  @override
  Widget build(BuildContext context) {
    double widthSize = MediaQuery.of(context).size.width;

    return GetBuilder<OrderController>(
      builder: (orderController) {
        return Container(height: 165, decoration: BoxDecoration(
          color: Theme.of(context).primaryColor,
          borderRadius:  BorderRadius.only(bottomLeft: Radius.circular(Dimensions.paddingSizeOverLarge),
              bottomRight: Radius.circular(Dimensions.paddingSizeOverLarge))),
          padding:  EdgeInsets.only(top : Dimensions.paddingSizeSmall),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            GetBuilder<OrderController>(
            builder: (order) {
              return Padding(padding:  EdgeInsets.fromLTRB(Dimensions.paddingSizeDefault,
                    Dimensions.fontSizeExtraSmall, Dimensions.paddingSizeDefault, Dimensions.dashWidth),
                child: Container(height: 55, decoration: BoxDecoration(
                  color: (
                    Get.find<ThemeController>().darkTheme ?
                    ColorHelper.blendColors(Colors.white, Theme.of(context).primaryColor, 0.9):
                    ColorHelper.darken(Theme.of(context).primaryColor, 0.1)
                  ),
                  borderRadius: BorderRadius.circular(Dimensions.searchRadius)),
                    child: Padding(
                      padding: const EdgeInsets.only(left: 8.0),
                      child: ListView(
                        shrinkWrap: true,
                        scrollDirection: Axis.horizontal,
                        children: [
                          OrderTypeButtonWidget(text: 'all'.tr, index: 0),

                          OrderTypeButtonWidget(text: 'out_for_delivery'.tr, index: 1),

                          OrderTypeButtonWidget(text: 'paused'.tr, index: 2),

                          OrderTypeButtonWidget(text: 'delivered'.tr, index: 3),

                          OrderTypeButtonWidget(text: 'return'.tr, index: 4),

                          OrderTypeButtonWidget(text: 'canceled'.tr, index: 5),
                        ],
                      ),
                    ),
                ),
              );},
            ),

            Expanded(
              child: Stack(children: [
                Padding(
                  padding: EdgeInsets.only(left: widthSize / 5, right: Dimensions.paddingSizeDefault),
                  child: const DeliverySearchFilterWidget(fromOrderHistory : true),
                ),

                Padding(
                  padding: EdgeInsets.only(left: Dimensions.paddingSizeDefault, bottom: Dimensions.radiusSmall, right: Dimensions.paddingSizeDefault),
                  child: CustomSearchWidget(
                    keyboardType: TextInputType.number,
                      inputFormatters: <TextInputFormatter>[
                        FilteringTextInputFormatter.digitsOnly
                      ],
                      width: widthSize,
                      hintStyle: rubikRegular.copyWith(fontSize: Dimensions.fontSizeDefault, color: Theme.of(context).hintColor,),
                      style: rubikRegular.copyWith(fontSize: Dimensions.fontSizeDefault),
                      textController: orderController.searchOrderController,
                      onSuffixTap: () => orderController.setSearchText(searchText: '', isUpdate: false),
                      onChanged: (value){
                        if(value != null){
                          debounceHelper.run(()=> orderController.setOrderTypeIndex(
                              orderController.orderTypeIndex,
                              search: orderController.searchOrderController.text
                          ));
                        }
                      },
                      color: Get.isDarkMode ? Theme.of(context).highlightColor : Theme.of(context).cardColor,
                      helpText: "search_by_order_id".tr,
                      autoFocus: true,
                      closeSearchOnSuffixTap: true,
                      animationDurationInMilli: 200,
                      rtl: false),
                ),
              ]),
            ),

        ],),);
      }
    );
  }
}
