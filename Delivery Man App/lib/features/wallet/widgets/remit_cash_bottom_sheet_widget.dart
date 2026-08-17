import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_button_widget.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_snackbar_widget.dart';
import 'package:sixvalley_delivery_boy/features/profile/controllers/profile_controller.dart';
import 'package:sixvalley_delivery_boy/features/wallet/controllers/wallet_controller.dart';
import 'package:sixvalley_delivery_boy/helper/price_converter.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:url_launcher/url_launcher.dart';

class RemitCashBottomSheetWidget extends StatefulWidget {
  const RemitCashBottomSheetWidget({Key? key}) : super(key: key);

  @override
  State<RemitCashBottomSheetWidget> createState() => _RemitCashBottomSheetWidgetState();
}

class _RemitCashBottomSheetWidgetState extends State<RemitCashBottomSheetWidget> {
  final TextEditingController _amountController = TextEditingController();
  final FocusNode _amountFocusNode = FocusNode();

  @override
  void dispose() {
    _amountController.dispose();
    _amountFocusNode.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<ProfileController>(
      builder: (profileController) {
        final double cashInHand = profileController.profileModel?.cashInHand ?? 0.0;
        final bool isDark = Get.isDarkMode;

        return Container(
          padding: EdgeInsets.only(
            left: Dimensions.paddingSizeDefault,
            right: Dimensions.paddingSizeDefault,
            top: Dimensions.paddingSizeDefault,
            bottom: MediaQuery.of(context).viewInsets.bottom + Dimensions.paddingSizeDefault,
          ),
          decoration: BoxDecoration(
            color: isDark ? Theme.of(context).canvasColor : Theme.of(context).cardColor,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
          ),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Theme.of(context).hintColor.withValues(alpha: 0.3),
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Remit Cash in Hand'.tr,
                      style: rubikBold.copyWith(
                        fontSize: Dimensions.fontSizeLarge,
                        color: isDark ? Colors.white : const Color(0xFF4A148C),
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Get.back(),
                    ),
                  ],
                ),
                const SizedBox(height: 8),

                // Cash in Hand Info Card
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: const Color(0xFF4A148C).withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: const Color(0xFF4A148C).withValues(alpha: 0.2)),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Current Cash in Hand'.tr,
                            style: rubikRegular.copyWith(
                              fontSize: Dimensions.fontSizeSmall,
                              color: isDark ? Colors.white70 : Colors.black87,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            PriceConverter.convertPrice(cashInHand),
                            style: rubikBold.copyWith(
                              fontSize: 20,
                              color: const Color(0xFF4A148C),
                            ),
                          ),
                        ],
                      ),
                      InkWell(
                        onTap: () {
                          if (cashInHand > 0) {
                            setState(() {
                              _amountController.text = cashInHand.toStringAsFixed(0);
                            });
                          }
                        },
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          decoration: BoxDecoration(
                            color: const Color(0xFFFFD700),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            'Remit All'.tr,
                            style: rubikBold.copyWith(
                              fontSize: Dimensions.fontSizeSmall,
                              color: const Color(0xFF4A148C),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),

                Text(
                  'Enter Remittance Amount'.tr,
                  style: rubikMedium.copyWith(
                    fontSize: Dimensions.fontSizeDefault,
                    color: isDark ? Colors.white70 : Colors.black87,
                  ),
                ),
                const SizedBox(height: 8),

                TextField(
                  controller: _amountController,
                  focusNode: _amountFocusNode,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  style: rubikBold.copyWith(fontSize: 18),
                  decoration: InputDecoration(
                    hintText: '0.00',
                    prefixIcon: Container(
                      padding: const EdgeInsets.all(14),
                      child: Text(
                        '₦',
                        style: rubikBold.copyWith(
                          fontSize: 18,
                          color: Theme.of(context).primaryColor,
                        ),
                      ),
                    ),
                    filled: true,
                    fillColor: isDark ? Colors.grey[850] : Colors.grey[100],
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(10),
                      borderSide: BorderSide.none,
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(10),
                      borderSide: BorderSide(color: Theme.of(context).primaryColor, width: 1.5),
                    ),
                  ),
                ),
                const SizedBox(height: 12),

                Text(
                  'ℹ️ You will be redirected to Paystack to complete the payment via Bank Transfer, Debit Card, or USSD. Once paid, your cash in hand balance updates automatically.'.tr,
                  style: rubikRegular.copyWith(
                    fontSize: Dimensions.fontSizeExtraSmall,
                    color: isDark ? Colors.white60 : Colors.grey[600],
                  ),
                ),
                const SizedBox(height: 24),

                GetBuilder<WalletController>(
                  builder: (walletController) {
                    return walletController.isLoading
                        ? const Center(child: CircularProgressIndicator())
                        : CustomButtonWidget(
                            btnTxt: 'Pay with Paystack'.tr,
                            onTap: () async {
                              final String text = _amountController.text.trim();
                              final double? enteredAmount = double.tryParse(text);

                              if (enteredAmount == null || enteredAmount <= 0) {
                                showCustomSnackBarWidget('Please enter a valid remittance amount'.tr);
                                return;
                              }

                              if (enteredAmount > cashInHand) {
                                showCustomSnackBarWidget('Remittance amount cannot exceed cash in hand'.tr);
                                return;
                              }

                              final String? authUrl = await walletController.remitCashViaPaystack(enteredAmount);

                              if (authUrl != null) {
                                Get.back();
                                if (await canLaunchUrl(Uri.parse(authUrl))) {
                                  await launchUrl(Uri.parse(authUrl), mode: LaunchMode.externalApplication);
                                  // Refresh profile after returning
                                  Future.delayed(const Duration(seconds: 2), () {
                                    Get.find<ProfileController>().getProfile();
                                  });
                                } else {
                                  showCustomSnackBarWidget('Could not launch Paystack payment page'.tr);
                                }
                              }
                            },
                          );
                  },
                ),
                const SizedBox(height: 10),
              ],
            ),
          ),
        );
      },
    );
  }
}
