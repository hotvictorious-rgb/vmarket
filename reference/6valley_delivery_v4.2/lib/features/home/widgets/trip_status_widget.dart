import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:intl/intl.dart';
import 'package:sixvalley_delivery_boy/features/dashboard/controllers/dashboard_controller.dart';
import 'package:sixvalley_delivery_boy/features/order/controllers/order_controller.dart';
import 'package:sixvalley_delivery_boy/features/profile/controllers/profile_controller.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:sixvalley_delivery_boy/features/dashboard/screens/dashboard_screen.dart';

class TripStatusWidget extends StatelessWidget {
  final Function(int index) onTap;
  const TripStatusWidget({Key? key, required this.onTap}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Padding(padding:  EdgeInsets.symmetric(horizontal : Dimensions.paddingSizeExtraLarge,vertical: Dimensions.paddingSizeExtraSmall),
      child: GetBuilder<ProfileController>(
        builder: (profileController) {
          return Column(crossAxisAlignment: CrossAxisAlignment.start,children: [
            Padding(padding:  EdgeInsets.only(bottom: Dimensions.paddingSizeSmall),
              child: Text('order_status'.tr,style:  rubikMedium.copyWith(fontSize: Dimensions.fontSizeExtraLarge,))),

            GestureDetector(onTap: ()=> Get.to(()=> const DashboardScreen(pageIndex: 1)),
              child: TripItem(color: Theme.of(context).colorScheme.tertiaryContainer, icon: Images.assigned,
                  title: 'assigned', totalCount: profileController.profileModel != null?
                profileController.profileModel!.totalDelivery ?? 0 : 0,
                onTap: (){
                Get.find<DashboardController>().selectOrderHistoryScreen(fromHome: true);
                Get.find<OrderController>().setOrderTypeIndex(0);
                onTap(1);})),

            TripItem(color: Theme.of(context).colorScheme.tertiary, icon: Images.pending,
                title: 'paused',totalCount: profileController.profileModel?.pauseDelivery ?? 0,
              onTap: () {
                 Get.find<DashboardController>().selectOrderHistoryScreen(fromHome: true);
                 onTap(1);
                  Get.find<OrderController>().setOrderTypeIndex(2, reload: true);}),

            TripItem(color: Theme.of(context).colorScheme.primaryContainer,icon: Images.completed,
                title: 'delivered', totalCount: profileController.profileModel?.completedDelivery??0,
              onTap: (){
                Get.find<DashboardController>().selectOrderHistoryScreen(fromHome: true);
                onTap(1);
                Get.find<OrderController>().setOrderTypeIndex(3, reload: true);}),
          ],);
        }
      ),
    );
  }
}

class TripItem extends StatelessWidget {
  final Color? color;
  final String? icon;
  final String? title;
  final int? totalCount;
  final Function? onTap;
  const TripItem({Key? key, this.icon, this.title, this.totalCount, this.color, this.onTap}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap as void Function()?,
      child: Padding(padding:  EdgeInsets.only(top: Dimensions.paddingSizeChat),
        child: Container(decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(Dimensions.paddingSizeChat),
          color: Theme.of(context).cardColor,
          boxShadow: [ BoxShadow(color: Theme.of(context).textTheme.bodyLarge!.color!.withValues(alpha:.11), spreadRadius: 0, blurRadius: 4, offset: const Offset(1, 1))],
        ),
            child: Container(decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(Dimensions.paddingSizeChat),
              color: color!.withValues(alpha:.50),
            ),
              child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween,children: [
               Container(padding:  EdgeInsets.symmetric(horizontal : Dimensions.paddingSizeSmall, vertical: Dimensions.paddingSizeSmall),
                 child: Row(children: [
                  Padding(padding:  EdgeInsets.all(Dimensions.paddingSizeSmall),
                    child: SizedBox(width: 30,child: Image.asset(icon!))),
                   Text(title!.tr, style: rubikRegular.copyWith(fontSize: Dimensions.fontSizeLarge, fontWeight: FontWeight.w400, color: Get.isDarkMode ? Theme.of(context).hintColor : Theme.of(context).textTheme.bodyLarge?.color))])),

                Padding(padding:  EdgeInsets.symmetric(horizontal : Dimensions.rememberMeSizeDefault, vertical: Dimensions.paddingSizeDefault),
                  child: Container(padding:  EdgeInsets.symmetric(vertical : Dimensions.paddingSizeSmall,
                      horizontal: Dimensions.paddingSizeDefault),
                    decoration: BoxDecoration(borderRadius: BorderRadius.circular(Dimensions.paddingSizeMin),
                        color: color!.withValues(alpha:.45)),
                    child: Text(totalCount! < 10 ? "0$totalCount" : NumberFormat.compact().format(totalCount),
                      style: rubikMedium.copyWith(fontSize: Dimensions.fontSizeExtraLarge, color: Get.isDarkMode ? Theme.of(context).hintColor : Theme.of(context).textTheme.bodyLarge?.color))))]),
            ))),
    );
  }
}
