import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/helper/color_helper.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';

class CustomButtonWidget extends StatelessWidget {
  final Function? onTap;
  final String btnTxt;
  final bool isShowBorder;
  final bool transparent;
  final bool withIcon;
  final IconData? icon;

  const CustomButtonWidget({Key? key, this.onTap, required this.btnTxt, this.isShowBorder = false, this.transparent = false, this.withIcon = false, this.icon}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap as void Function()?,
      child: Container(
        height: 50,
        width: MediaQuery.of(context).size.width,
        decoration: BoxDecoration(
            boxShadow: [BoxShadow(color: !isShowBorder ? Get.isDarkMode ? Theme.of(context).primaryColor.withValues(alpha:.5) : Colors.grey.withValues(alpha:0.2) : Colors.transparent,
                spreadRadius: 1, blurRadius: 7, offset: const Offset(0, 1))],
            borderRadius: BorderRadius.circular(25),
            border: Border.all(color: isShowBorder && !transparent ? ColorHelper.blendColors(Colors.white, Theme.of(context).hintColor, 0.6) : Colors.transparent),
            color: !isShowBorder ? Get.isDarkMode ? ColorHelper.blendColors(Colors.white, Theme.of(context).primaryColor, 0.9).withValues(alpha: .8) : Theme.of(context).primaryColor : Colors.transparent),
        child: Row(mainAxisAlignment: MainAxisAlignment.center,crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            withIcon?
            Icon(icon, color: Get.isDarkMode? Theme.of(context).hintColor.withValues(alpha:.5) : Theme.of(context).cardColor) : const SizedBox(),
            TextButton(
              onPressed: onTap as void Function()?,
              style: TextButton.styleFrom(padding: const EdgeInsets.all(0)),
              child: Text(btnTxt,
                  style: rubikBold.copyWith(
                      color: Get.isDarkMode ? Theme.of(context).textTheme.bodyLarge?.color : isShowBorder ? Theme.of(context).textTheme.bodyLarge!.color :
                      Theme.of(context).cardColor, fontSize: Dimensions.fontSizeLarge),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
