import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_button_widget.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_image_widget.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/no_internet_screen_widget.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/show_custom_snakbar_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/controllers/order_details_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/controllers/splash_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/domain/models/config_model.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/main.dart';
import 'package:flutter_sixvalley_ecommerce/theme/controllers/theme_controller.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:provider/provider.dart';

class OrderPaymentMethodBottomSheetWidget extends StatefulWidget {
  final String orderId;
  final double payableAmount;
  final bool onlyDigital;
  const OrderPaymentMethodBottomSheetWidget({
    super.key,
    required this.onlyDigital,
    required this.orderId,
    required this.payableAmount,
  });

  @override
  OrderPaymentMethodBottomSheetWidgetState createState() => OrderPaymentMethodBottomSheetWidgetState();
}

class OrderPaymentMethodBottomSheetWidgetState extends State<OrderPaymentMethodBottomSheetWidget> {
  final ConfigModel? configModel = Provider.of<SplashController>(Get.context!, listen: false).configModel;

  @override
  Widget build(BuildContext context) {
    return Consumer<OrderDetailsController>(
      builder: (context, orderDetailsController, _) {
        return Container(
          constraints: BoxConstraints(
            maxHeight: MediaQuery.of(context).size.height * 0.7,
            minHeight: MediaQuery.of(context).size.height * 0.4,
          ),
          padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
          decoration: BoxDecoration(
            color: Theme.of(context).highlightColor,
            borderRadius: const BorderRadius.only(
              topLeft: Radius.circular(20),
              topRight: Radius.circular(20),
            ),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Expanded(
                child: SingleChildScrollView(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const SizedBox(height: Dimensions.paddingSizeSmall),
                      Center(
                        child: Container(
                          width: 35,
                          height: 4,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(Dimensions.paddingSizeDefault),
                            color: Theme.of(context).hintColor.withValues(alpha: .5),
                          ),
                        ),
                      ),
                      const SizedBox(height: Dimensions.paddingSizeDefault),

                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeSmall),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              getTranslated('choose_payment_method', context) ?? 'Choose Payment Method',
                              style: titilliumSemiBold.copyWith(
                                fontSize: Dimensions.fontSizeDefault,
                                color: Theme.of(context).textTheme.bodyLarge?.color,
                              ),
                            ),
                            Expanded(
                              child: Padding(
                                padding: const EdgeInsets.only(left: Dimensions.paddingSizeExtraSmall),
                                child: Text(
                                  getTranslated('click_one_of_the_option_below', context) ?? '',
                                  style: textRegular.copyWith(
                                    color: Theme.of(context).hintColor,
                                    fontSize: Dimensions.fontSizeSmall,
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),

                      if (configModel?.paymentMethods != null && configModel!.paymentMethods!.isNotEmpty)
                        Container(
                          padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                          decoration: BoxDecoration(
                            color: Theme.of(context).cardColor,
                            border: Border.all(
                              width: 1,
                              color: Theme.of(context).hintColor.withValues(alpha: .125),
                            ),
                            borderRadius: BorderRadius.circular(Dimensions.paddingSizeExtraSmall),
                          ),
                          child: Column(
                            children: [
                              Padding(
                                padding: const EdgeInsets.only(bottom: Dimensions.paddingSizeSmall, top: Dimensions.paddingSizeExtraSmall),
                                child: Row(
                                  children: [
                                    Text(
                                      getTranslated('pay_via_online', context) ?? 'Pay Online',
                                      style: titilliumBold.copyWith(
                                        color: Theme.of(context).textTheme.bodyLarge?.color,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              Consumer<SplashController>(
                                builder: (context, configProvider, _) {
                                  return ListView.separated(
                                    padding: EdgeInsets.zero,
                                    itemCount: configProvider.configModel?.paymentMethods?.length ?? 0,
                                    shrinkWrap: true,
                                    physics: const NeverScrollableScrollPhysics(),
                                    itemBuilder: (context, index) {
                                      return CustomCheckBoxWidget(
                                        index: index,
                                        padding: 0,
                                        icon: '${configProvider.configModel?.paymentMethodImagePath}/'
                                            '${configProvider.configModel?.paymentMethods?[index].additionalDatas?.gatewayImage ?? ''}',
                                        name: configProvider.configModel!.paymentMethods![index].keyName!,
                                        title: configProvider.configModel!.paymentMethods![index].additionalDatas?.gatewayTitle ?? '',
                                      );
                                    },
                                    separatorBuilder: (context, index) => const SizedBox(height: Dimensions.paddingSizeSmall),
                                  );
                                },
                              ),
                            ],
                          ),
                        )
                      else
                        const NoInternetOrDataScreenWidget(
                          isNoInternet: false,
                          message: 'no_payment_method_available_right_now',
                        ),
                    ],
                  ),
                ),
              ),

              CustomButton(
                isLoading: orderDetailsController.isLoading,
                buttonText: getTranslated('proceed_text', context) ?? 'Proceed',
                onTap: () async {
                  if (orderDetailsController.paymentMethodIndex != -1) {
                    await orderDetailsController.duePaymentByDigitalPayment(
                      int.parse(widget.orderId),
                      orderDetailsController.selectedDigitalPaymentMethodName,
                      'digital_payment',
                      '',
                    );
                  } else {
                    showCustomSnackBar(
                      getTranslated('select_payment_method', context) ?? 'Please select a payment method',
                      context,
                    );
                  }
                },
              ),
            ],
          ),
        );
      },
    );
  }
}

class CustomCheckBoxWidget extends StatelessWidget {
  final int index;
  final bool isDigital;
  final String? icon;
  final String name;
  final String title;
  final double? padding;
  const CustomCheckBoxWidget({
    super.key,
    required this.index,
    this.isDigital = false,
    this.icon,
    required this.name,
    required this.title,
    this.padding,
  });

  @override
  Widget build(BuildContext context) {
    return Consumer<OrderDetailsController>(
      builder: (context, order, child) {
        return InkWell(
          onTap: () => order.setDigitalPaymentMethodName(index, name),
          child: Padding(
            padding: EdgeInsets.all(padding ?? Dimensions.paddingSizeDefault),
            child: Container(
              decoration: BoxDecoration(borderRadius: BorderRadius.circular(Dimensions.paddingSizeExtraSmall)),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.start,
                children: [
                  SizedBox(
                    height: 40,
                    child: Padding(
                      padding: const EdgeInsets.all(Dimensions.paddingSizeExtraSmall),
                      child: CustomImageWidget(image: icon!),
                    ),
                  ),
                  Expanded(
                    child: Text(
                      title,
                      style: textRegular.copyWith(
                        fontSize: Dimensions.fontSizeLarge,
                        color: Theme.of(context).textTheme.bodyLarge?.color,
                      ),
                    ),
                  ),
                  Theme(
                    data: Theme.of(context).copyWith(
                      unselectedWidgetColor: Provider.of<ThemeController>(context, listen: false).darkTheme
                          ? Theme.of(context).hintColor.withValues(alpha: .5)
                          : Theme.of(context).primaryColor.withValues(alpha: .25),
                    ),
                    child: Checkbox(
                      visualDensity: VisualDensity.compact,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.paddingSizeExtraLarge)),
                      value: order.paymentMethodIndex == index,
                      activeColor: Colors.green,
                      checkColor: Theme.of(context).cardColor,
                      onChanged: (bool? isChecked) => order.setDigitalPaymentMethodName(index, name),
                    ),
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }
}