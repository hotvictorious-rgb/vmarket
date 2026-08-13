import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_asset_image_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/controllers/checkout_controller.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/controllers/splash_controller.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:flutter_sixvalley_ecommerce/utill/images.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_image_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/widgets/payment_method_bottom_sheet_widget.dart';
import 'package:provider/provider.dart';

class ChoosePaymentWidget extends StatelessWidget {
  final bool onlyDigital;
  const ChoosePaymentWidget({super.key, required this.onlyDigital});

  @override
  Widget build(BuildContext context) {
    return Consumer<CheckoutController>(
      builder: (context, orderProvider, _) {
        return Consumer<SplashController>(
          builder: (context, configProvider, _) {
            bool hasSelection = orderProvider.isCODChecked ||
                orderProvider.isOfflineChecked ||
                orderProvider.isWalletChecked ||
                (orderProvider.paymentMethodIndex != -1);

            return Container(
              decoration: BoxDecoration(
                color: Theme.of(context).cardColor,
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.04),
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  ),
                ],
                border: Border.all(
                  color: Theme.of(context).primaryColor.withValues(alpha: 0.08),
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Section Header
                  Padding(
                    padding: const EdgeInsets.fromLTRB(Dimensions.paddingSizeDefault, Dimensions.paddingSizeDefault, Dimensions.paddingSizeDefault, Dimensions.paddingSizeSmall),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(6),
                              decoration: BoxDecoration(
                                color: const Color(0xFF6A1B9A).withValues(alpha: 0.08),
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(Icons.payment, size: 16, color: Color(0xFF6A1B9A)),
                            ),
                            const SizedBox(width: 10),
                            Text(
                              getTranslated('payment_method', context) ?? 'Payment Method',
                              style: textBold.copyWith(
                                fontSize: Dimensions.fontSizeLarge,
                                color: Theme.of(context).textTheme.bodyLarge?.color,
                              ),
                            ),
                          ],
                        ),
                        InkWell(
                          onTap: () => showModalBottomSheet(
                            context: context,
                            isScrollControlled: true,
                            backgroundColor: Colors.transparent,
                            builder: (c) => PaymentMethodBottomSheetWidget(onlyDigital: onlyDigital),
                          ),
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: const Color(0xFF6A1B9A).withValues(alpha: 0.08),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(
                              hasSelection ? 'Change' : 'Select',
                              style: textBold.copyWith(
                                fontSize: Dimensions.fontSizeSmall,
                                color: const Color(0xFF6A1B9A),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),

                  const Divider(height: 1, thickness: 0.5),

                  // Selected Payment Method Interactive Card
                  Padding(
                    padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                    child: InkWell(
                      onTap: () => showModalBottomSheet(
                        context: context,
                        isScrollControlled: true,
                        backgroundColor: Colors.transparent,
                        builder: (c) => PaymentMethodBottomSheetWidget(onlyDigital: onlyDigital),
                      ),
                      borderRadius: BorderRadius.circular(14),
                      child: Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: hasSelection
                              ? const Color(0xFF6A1B9A).withValues(alpha: 0.04)
                              : Theme.of(context).cardColor,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(
                            color: hasSelection
                                ? const Color(0xFF6A1B9A)
                                : Theme.of(context).hintColor.withValues(alpha: 0.2),
                            width: hasSelection ? 1.5 : 1,
                          ),
                        ),
                        child: Row(
                          children: [
                            // Gateway Icon or Default Icon
                            if (orderProvider.paymentMethodIndex != -1 && configProvider.configModel?.paymentMethods != null)
                              Container(
                                height: 38, width: 50,
                                padding: const EdgeInsets.all(4),
                                decoration: BoxDecoration(
                                  color: Colors.white,
                                  borderRadius: BorderRadius.circular(8),
                                  border: Border.all(color: Colors.black.withValues(alpha: 0.06)),
                                ),
                                child: CustomImageWidget(
                                  image: '${configProvider.configModel?.paymentMethodImagePath}/${configProvider.configModel!.paymentMethods![orderProvider.paymentMethodIndex].additionalDatas?.gatewayImage ?? ''}',
                                  fit: BoxFit.contain,
                                ),
                              )
                            else if (orderProvider.isCODChecked)
                              Container(
                                height: 38, width: 50,
                                padding: const EdgeInsets.all(6),
                                decoration: BoxDecoration(
                                  color: const Color(0xFF6A1B9A).withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Image.asset(Images.cod, fit: BoxFit.contain),
                              )
                            else if (orderProvider.isWalletChecked)
                              Container(
                                height: 38, width: 50,
                                padding: const EdgeInsets.all(6),
                                decoration: BoxDecoration(
                                  color: const Color(0xFF6A1B9A).withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Image.asset(Images.payWallet, fit: BoxFit.contain),
                              )
                            else if (orderProvider.isOfflineChecked)
                              Container(
                                height: 38, width: 50,
                                padding: const EdgeInsets.all(6),
                                decoration: BoxDecoration(
                                  color: const Color(0xFF6A1B9A).withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: const Icon(Icons.account_balance, color: Color(0xFF6A1B9A), size: 24),
                              )
                            else
                              Container(
                                height: 38, width: 50,
                                decoration: BoxDecoration(
                                  color: Colors.grey.withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: const Icon(Icons.add_card, color: Colors.grey, size: 22),
                              ),

                            const SizedBox(width: Dimensions.paddingSizeDefault),

                            // Payment Title & Subtitle
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    orderProvider.paymentMethodIndex != -1 && configProvider.configModel?.paymentMethods != null
                                        ? (configProvider.configModel!.paymentMethods![orderProvider.paymentMethodIndex].additionalDatas?.gatewayTitle ?? '')
                                        : orderProvider.isCODChecked
                                            ? (getTranslated('cash_on_delivery', context) ?? 'Cash On Delivery')
                                            : orderProvider.isOfflineChecked
                                                ? (getTranslated('offline_payment', context) ?? 'Offline Payment')
                                                : orderProvider.isWalletChecked
                                                    ? (getTranslated('wallet_payment', context) ?? 'Wallet Payment')
                                                    : (getTranslated('add_payment_method', context) ?? 'Choose Payment Method'),
                                    style: textBold.copyWith(
                                      fontSize: Dimensions.fontSizeDefault,
                                      color: hasSelection
                                          ? const Color(0xFF4A148C)
                                          : Theme.of(context).textTheme.bodyLarge?.color,
                                    ),
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    hasSelection ? 'Tap to switch payment method' : 'Select your preferred gateway',
                                    style: textRegular.copyWith(
                                      fontSize: Dimensions.fontSizeExtraSmall,
                                      color: Theme.of(context).hintColor,
                                    ),
                                  ),
                                ],
                              ),
                            ),

                            // Checkmark or Arrow
                            if (hasSelection)
                              Container(
                                height: 22, width: 22,
                                decoration: const BoxDecoration(
                                  shape: BoxShape.circle,
                                  color: Color(0xFF6A1B9A),
                                ),
                                child: const Icon(Icons.check, color: Colors.white, size: 14),
                              )
                            else
                              const Icon(Icons.keyboard_arrow_right, color: Colors.grey, size: 20),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }
}
