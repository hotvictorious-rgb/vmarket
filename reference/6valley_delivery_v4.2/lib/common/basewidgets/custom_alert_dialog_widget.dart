import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_button_widget.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';


class CustomAlertDialogWidget extends StatelessWidget {
  final String description;
  final Function onOkPressed;
  const CustomAlertDialogWidget({Key? key, required this.description, required this.onOkPressed}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.radiusSmall)),
      child: Padding(
        padding:  EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeLarge, vertical: Dimensions.paddingSizeSmall),
        child: Column(mainAxisSize: MainAxisSize.min, children: [

          Icon(Icons.info, size: 80, color: Theme.of(context).primaryColor),

          Padding(
            padding:  EdgeInsets.all(Dimensions.paddingSizeLarge),
            child: Text(
              description, textAlign: TextAlign.center,
              style: rubikMedium.copyWith(fontSize: Dimensions.fontSizeLarge),
            ),
          ),

          CustomButtonWidget(
            btnTxt: 'ok'.tr,
            onTap: onOkPressed,
          ),

        ]),
      ),
    );
  }
}
