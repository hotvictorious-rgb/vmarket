import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/online_offline_button_widget.dart';


class CustomAppBarWidget extends StatelessWidget implements PreferredSizeWidget {
  final String? title;
  final bool isBack;
  final Function()? onTap;
  final bool isSwitch;
  final bool isCenterTitle;

  const CustomAppBarWidget({Key? key, this.title, this.isBack = false, this.onTap, this.isSwitch  = false, this.isCenterTitle = true}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return  PreferredSize(
      preferredSize: preferredSize,
      child: AppBar(
        centerTitle: isCenterTitle,
        leading: isBack ? InkWell(onTap: onTap  ?? () => Get.back(),
            child: Icon(Icons.arrow_back_ios,color: Get.isDarkMode ? Theme.of(context).hintColor.withValues(alpha:.5) : Theme.of(context).textTheme.bodyLarge?.color)) :
        Padding(padding:  EdgeInsets.all(Dimensions.paddingSizeSmall),
          child: Image.asset(Images.splashLogo, height: 30, width: 30),),
        titleSpacing: 0,
        elevation: 1,
        title: Text(title!.tr, maxLines: 1, overflow: TextOverflow.ellipsis, style: rubikMedium.copyWith(
          color: Theme.of(context).textTheme.bodyLarge?.color, fontSize: Dimensions.fontSizeLarge,)),
        actions:  [
          isSwitch?
          const OnlineOfflineButtonWidget(): const SizedBox(),
           SizedBox(width: Dimensions.paddingSizeSmall),
        ],
      ),
    );
  }

  @override
  Size get preferredSize => const Size(1170, 50);
}