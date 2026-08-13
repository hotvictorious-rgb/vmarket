import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/controllers/order_details_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/controllers/splash_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/domain/models/config_model.dart';
import 'package:flutter_sixvalley_ecommerce/helper/date_converter.dart';
import 'package:flutter_sixvalley_ecommerce/helper/price_converter.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:provider/provider.dart';

class OrderPaymentInfoWidget extends StatelessWidget {
  const OrderPaymentInfoWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return Consumer<OrderDetailsController>(
      builder: (context, orderProvider, child) {
        ConfigModel? configModel = Provider.of<SplashController>(context, listen: false).configModel;

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

          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
            child: Column(
              children: [
                const SizedBox(height: Dimensions.paddingSizeDefault),

                if(configModel?.orderVerification == 1 && orderProvider.orders!.orderType != 'POS')...[
                  (() {
                    bool isCodeHidden = true;
                    return StatefulBuilder(
                      builder: (context, setState) {
                        return Container(
                          margin: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeExtraSmall),
                          padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                              colors: [Color(0xFFFFF8E1), Color(0xFFFFECB3)],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: const Color(0xFFFFD700), width: 1.5),
                            boxShadow: [
                              BoxShadow(
                                color: const Color(0xFFFFD700).withValues(alpha: 0.15),
                                blurRadius: 8,
                                offset: const Offset(0, 3),
                              ),
                            ],
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Row(
                                    children: [
                                      const Icon(Icons.shield_rounded, color: Color(0xFF6A1B9A), size: 20),
                                      const SizedBox(width: 6),
                                      Text(
                                        getTranslated('order_verification_code', context) ?? 'Secret Handover OTP',
                                        style: titilliumBold.copyWith(
                                          color: const Color(0xFF4A148C),
                                          fontSize: Dimensions.fontSizeDefault,
                                        ),
                                      ),
                                    ],
                                  ),
                                  Row(
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: Colors.white,
                                          borderRadius: BorderRadius.circular(8),
                                          border: Border.all(color: const Color(0xFFFFD700)),
                                        ),
                                        child: Text(
                                          isCodeHidden ? '••••••' : (orderProvider.orders?.verificationCode ?? ''),
                                          style: robotoBold.copyWith(
                                            color: const Color(0xFF4A148C),
                                            fontSize: Dimensions.fontSizeLarge,
                                            letterSpacing: 2,
                                          ),
                                        ),
                                      ),
                                      const SizedBox(width: Dimensions.paddingSizeSmall),
                                      InkWell(
                                        onTap: () {
                                          setState(() {
                                            isCodeHidden = !isCodeHidden;
                                          });
                                        },
                                        child: Icon(
                                          isCodeHidden ? Icons.visibility_off : Icons.visibility,
                                          size: 22,
                                          color: const Color(0xFF6A1B9A),
                                        ),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                              const SizedBox(height: 6),
                              Text(
                                '⚠️ Give this code to rider ONLY when your package is received and inspected.',
                                style: textRegular.copyWith(
                                  fontSize: Dimensions.fontSizeExtraSmall,
                                  color: const Color(0xFF5D4037),
                                ),
                              ),
                            ],
                          ),
                        );
                      },
                    );
                  })(),
                  const SizedBox(height: Dimensions.paddingSizeSmall),
                ],


                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                        getTranslated('order_date_details', context) ?? '',
                        style: textRegular.copyWith(color: Theme.of(context).textTheme.titleMedium?.color)
                    ),

                    Text(
                      DateConverter.localDateToIsoStringAMPMOrder(DateTime.parse(orderProvider.orders!.createdAt!)),
                      style: textMedium.copyWith(color: Theme.of(context).textTheme.bodyLarge?.color, fontSize: Dimensions.fontSizeDefault)
                    ),
                  ],
                ),
                const SizedBox(height: Dimensions.paddingSizeSmall),
                SizedBox(height: 1, child: Divider(thickness: .200, color: Theme.of(context).hintColor.withValues(alpha: 0.45))),
                const SizedBox(height: Dimensions.paddingSizeSmall),



                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      getTranslated('order_type', context) ?? '',
                      style: textRegular.copyWith(color: Theme.of(context).textTheme.titleMedium?.color)
                    ),

                    Container(
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall),
                        color: Theme.of(context).primaryColor.withValues(alpha: 0.10),
                      ),
                      padding: const EdgeInsets.all(Dimensions.paddingSizeExtraSmall),
                      child: Text(
                          orderProvider.orders!.orderType == 'POS' ?
                          getTranslated('pos_order_small', context) ?? '' :
                          getTranslated('regular', context) ?? ' ',
                          style: textMedium.copyWith(color: Theme.of(context).primaryColor, fontSize: Dimensions.fontSizeSmall)
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: Dimensions.paddingSizeExtraSmall),
                SizedBox(height: 1, child: Divider(thickness: .200, color: Theme.of(context).hintColor.withValues(alpha: 0.45))),
                const SizedBox(height: Dimensions.paddingSizeSmall),



                ///todo: remove payment status and method from here as per new design
                // Row(
                //   mainAxisAlignment: MainAxisAlignment.spaceBetween,
                //   children: [
                //     Text(
                //       getTranslated('payment_status_title', context) ?? '',
                //       style: textRegular.copyWith(color: Theme.of(context).textTheme.titleMedium?.color)
                //     ),
                //
                //     Text((orderProvider.orders?.paymentStatus != null && orderProvider.orders!.paymentStatus!.isNotEmpty) ?
                //     getTranslated(orderProvider.orders!.paymentStatus, context) ?? orderProvider.orders!.paymentStatus!
                //       : 'Digital Payment',
                //       style: titilliumSemiBold.copyWith(
                //         fontSize: Dimensions.fontSizeDefault,
                //         color: orderProvider.orders?.paymentStatus == 'paid' ?
                //         Theme.of(context).colorScheme.onTertiaryContainer : Theme.of(context).colorScheme.error
                //       )
                //     )
                //   ],
                // ),
                // const SizedBox(height: Dimensions.paddingSizeSmall),
                // SizedBox(height: 1, child: Divider(thickness: .200, color: Theme.of(context).hintColor.withValues(alpha: 0.45))),
                // const SizedBox(height: Dimensions.paddingSizeSmall),
                //
                //
                // Row(
                //   mainAxisAlignment: MainAxisAlignment.spaceBetween,
                //   children: [
                //     Text(
                //       getTranslated('payment_method', context) ?? '',
                //       style: textRegular.copyWith(color: Theme.of(context).textTheme.titleMedium?.color)
                //     ),
                //
                //     Text(orderProvider.orders!.paymentMethod!.replaceAll('_', ' ').capitalize(),
                //         style: textMedium.copyWith(fontSize: Dimensions.fontSizeDefault, color: Theme.of(context).primaryColor)
                //     )
                //   ],
                // ),
                // const SizedBox(height: Dimensions.paddingSizeSmall),
                // SizedBox(height: 1, child: Divider(thickness: .200, color: Theme.of(context).hintColor.withValues(alpha: 0.45))),
                // const SizedBox(height: Dimensions.paddingSizeSmall),





                if(orderProvider.orders?.bringChangeAmount != null && orderProvider.orders!.bringChangeAmount! > 0)...[
                  Container(
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(Dimensions.radiusSmall),
                      boxShadow: [BoxShadow(color: Theme.of(context).hintColor.withValues(alpha:0.2), spreadRadius:3, blurRadius: 3)],
                      color: Theme.of(context).cardColor,
                    ),
                    padding: EdgeInsets.all(Dimensions.paddingSizeDefault),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          getTranslated('change_request', context) ?? '',
                          style: textBold.copyWith(color: Theme.of(context).textTheme.titleMedium?.color)
                        ),
                        SizedBox(height: Dimensions.paddingSizeSmall),

                        Container(
                          padding: EdgeInsets.symmetric(vertical: Dimensions.paddingSizeSmall, horizontal: Dimensions.paddingSizeDefault),
                          decoration: BoxDecoration(
                              color: Theme.of(context).colorScheme.onPrimary.withValues(alpha: .15),
                              borderRadius: BorderRadius.circular(Dimensions.radiusSmall)
                          ),
                          child:  RichText(text: TextSpan(children: [
                            TextSpan(text: getTranslated('please_bring', context),
                              style: titilliumRegular.copyWith(
                                fontSize: Dimensions.fontSizeSmall,
                                color: Theme.of(context).textTheme.titleLarge?.color,
                                fontWeight: FontWeight.w400,
                              ),
                            ),


                            TextSpan(text: '${PriceConverter.convertPrice(context, orderProvider.orders?.bringChangeAmount ?? 0)} ',
                              style: titilliumBold.copyWith(
                                fontSize: Dimensions.fontSizeSmall,
                                color: Theme.of(context).textTheme.titleLarge?.color,
                                fontWeight: FontWeight.w600,
                              ),
                            ),

                            TextSpan(text: getTranslated('in_change_when_making_the_delivery', context),
                              style: titilliumRegular.copyWith(
                                fontSize: Dimensions.fontSizeSmall,
                                color: Theme.of(context).textTheme.titleLarge?.color,
                                fontWeight: FontWeight.w400,
                              ),
                            ),

                          ])
                          ),
                        )



                      ],
                    )
                  ),
                  SizedBox(height: Dimensions.paddingSizeDefault),
                ]

              ],
            ),
          ),
        );
      }
    );
  }
}
