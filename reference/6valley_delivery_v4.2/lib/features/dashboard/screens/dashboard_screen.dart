import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/features/dashboard/controllers/dashboard_controller.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/animated_custom_dialog_widget.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/confirmation_dialog_widget.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_botom_navy_bar_widget.dart';

class DashboardScreen extends StatefulWidget {
  final int pageIndex;
  final int? chatIndex;
  const DashboardScreen({Key? key, required this.pageIndex, this.chatIndex}) : super(key: key);
  @override
  _DashboardScreenState createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {

  FlutterLocalNotificationsPlugin? flutterLocalNotificationsPlugin;
  final PageStorageBucket bucket = PageStorageBucket();

  @override
  void initState() {
    super.initState();
    Get.find<DashboardController>().selectHomePage(first: false);

    if(widget.pageIndex == 2) {
      Get.find<DashboardController>().selectConversationScreen(isUpdate: false, chatIndex: widget.chatIndex);
    }

    if(widget.pageIndex == 3) {
      Get.find<DashboardController>().selectNotificationScreen(isUpdate: false);
    }

    if(widget.pageIndex == 4) {
      Get.find<DashboardController>().selectProfileScreen(isUpdate: false);
    }

  }

  @override
  Widget build(BuildContext context) {
    return PopScope(canPop: false,
      onPopInvokedWithResult: (didPop, _) async {
        if( Get.find<DashboardController>().currentTab != 0) {
          Get.find<DashboardController>().selectHomePage();
        } else {
          _onWillPop(context);
        }
        return;
      },

      child: GetBuilder<DashboardController>(builder: (menuController) {
        return Scaffold(
          resizeToAvoidBottomInset: false,

          body: PageStorage(bucket: bucket, child: menuController.currentScreen!),

          bottomNavigationBar: BottomNavBarWidget(
            selectedIndex: menuController.currentTab,
            showElevation: true,
            animationDuration: const Duration(milliseconds: 500),
            itemCornerRadius: 100,
            curve: Curves.ease,
            items: [
              _barItem(Images.homeIcon, 'home'.tr, 0, menuController),
              _barItem(Images.orderIcon, 'order_history'.tr, 1, menuController),
              _barItem(Images.chatIcon, 'message'.tr, 2, menuController),
              _barItem(Images.notificationMenuIcon, 'notification'.tr, 3, menuController),
              _barItem(Images.profileIcon, 'profile'.tr, 4, menuController),
            ],
            onItemSelected: (int index) {
              if(index == 0){
                menuController.selectHomePage();
              }else if(index == 1){
                menuController.selectOrderHistoryScreen();
              }else if(index == 2){
                menuController.selectConversationScreen();
              }else if(index == 3){
                menuController.selectNotificationScreen();
              }else if(index == 4){
                menuController.selectProfileScreen();
              }
            },
          ),
        );
      }),
    );
  }

  BottomNavyBarItem _barItem(String icon, String label, int index, DashboardController menuController) {
    return BottomNavyBarItem(
      activeColor: Theme.of(context).primaryColor,
      textAlign: TextAlign.center,
      icon: index == menuController.currentTab ? const SizedBox() :
      SizedBox(width: Dimensions.iconSizeMenu,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 3),
          child: Image.asset(icon, color : index == menuController.currentTab ?
          Theme.of(context).cardColor : Theme.of(context).hintColor),
        )),
      title: Row(mainAxisAlignment: MainAxisAlignment.center, crossAxisAlignment: CrossAxisAlignment.center, children: [
        Image.asset(icon, color : index == menuController.currentTab ?
        Colors.white : Theme.of(context).hintColor,
          width: 16,
        ),

        SizedBox(width: Dimensions.paddingSizeSmall,),
        FittedBox(
          child: Text(label, style: rubikRegular.copyWith(color: index == menuController.currentTab ?
          Colors.white : Theme.of(context).hintColor, overflow: TextOverflow.ellipsis)),
        ),]));

  }


}
Future<bool> _onWillPop(BuildContext context) async {
  showAnimatedDialogWidget(context,  ConfirmationDialogWidget(icon: Images.logOut,
    title: 'exit_app'.tr,
    description: 'do_you_want_to_exit_the_app'.tr, onYesPressed: (){
    SystemNavigator.pop();
  },),isFlip: true);
  return true;
}


