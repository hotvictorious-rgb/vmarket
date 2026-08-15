import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';

class CustomAppBarEditProfileWidget extends StatelessWidget {
  const CustomAppBarEditProfileWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return Align(alignment: Alignment.topLeft, child: InkWell(
      onTap: () => Get.back(),
      child: Padding(
        padding: EdgeInsets.all(Dimensions.paddingSizeDefault),
        child: Row(children: [
          Padding(
            padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeMin),
            child: Icon(Icons.arrow_back_ios, color: Get.isDarkMode ? Theme.of(context).textTheme.bodyLarge?.color : Theme.of(context).cardColor,),
          ),

          Text('my_profile'.tr, style: rubikMedium.copyWith(
              fontSize: Dimensions.fontSizeExtraLarge,
              color: Get.isDarkMode ? Theme.of(context).textTheme.bodyLarge?.color :
              Theme.of(context).cardColor
          )),
        ]),
      ),
    ));
  }
}
