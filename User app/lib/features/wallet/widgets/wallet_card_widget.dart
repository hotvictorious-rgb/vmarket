import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/features/wallet/controllers/wallet_controller.dart';
import 'package:flutter_sixvalley_ecommerce/helper/price_converter.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:just_the_tooltip/just_the_tooltip.dart';
import 'package:provider/provider.dart';

class WalletCardWidget extends StatelessWidget {
  const WalletCardWidget({super.key, required this.tooltipController, required this.focusNode,
    required this.inputAmountController});

  final JustTheController tooltipController;
  final FocusNode focusNode;
  final TextEditingController inputAmountController;

  @override
  Widget build(BuildContext context) {
    return Consumer<WalletController>(builder: (context, walletController, _) {
      return Row(children: [
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisSize: MainAxisSize.min,
            mainAxisAlignment: MainAxisAlignment.center, children: [
              Text(getTranslated('wallet_amount', context)!,
                  style: textRegular.copyWith(color: Colors.white, fontSize: Dimensions.fontSizeLarge)),
              const SizedBox(height: Dimensions.paddingSizeSmall),

              Row(children: [
                Text(PriceConverter.convertPrice(context, (walletController.walletBalance != null && walletController.walletBalance?.totalWalletBalance != null) ?
                walletController.walletBalance!.totalWalletBalance ?? 0 : 0),
                    style: textBold.copyWith(color: Colors.white, fontSize: Dimensions.fontSizeOverLarge)),
                const SizedBox(width: Dimensions.paddingSizeExtraSmall),
              ]),
            ],
          ),
        ),
      ]);
    });
  }
}