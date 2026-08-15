import 'dart:async';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/features/auth/controllers/auth_controller.dart';
import 'package:sixvalley_delivery_boy/features/maintenance/maintenance_screen.dart';
import 'package:sixvalley_delivery_boy/features/notification/domain/models/notification_body.dart';
import 'package:sixvalley_delivery_boy/features/notification/screens/notification_screen.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/models/order_model.dart';
import 'package:sixvalley_delivery_boy/features/order_details/screens/order_details_screen.dart';
import 'package:sixvalley_delivery_boy/features/profile/controllers/profile_controller.dart';
import 'package:sixvalley_delivery_boy/features/splash/controllers/splash_controller.dart';
import 'package:sixvalley_delivery_boy/features/wallet/screens/wallet_screen.dart';
import 'package:sixvalley_delivery_boy/utill/app_constants.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:sixvalley_delivery_boy/features/auth/screens/login_screen.dart';
import 'package:sixvalley_delivery_boy/features/dashboard/screens/dashboard_screen.dart';
import 'package:sixvalley_delivery_boy/features/onboard/screens/onboarding_screen.dart';

class SplashScreen extends StatefulWidget {
  final NotificationBody? body;
  const SplashScreen({Key? key, this.body}) : super(key: key);

  @override
  _SplashScreenState createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  final GlobalKey<ScaffoldState> _globalKey = GlobalKey();
  late StreamSubscription<ConnectivityResult> _onConnectivityChanged;

  @override
  void initState() {
    super.initState();

    FirebaseMessaging.instance.subscribeToTopic(AppConstants.topic);
    FirebaseMessaging.instance.subscribeToTopic(AppConstants.maintenanceModeTopic);
    bool _firstTime = true;
    _onConnectivityChanged = Connectivity().onConnectivityChanged.listen((ConnectivityResult result) {
      if(!_firstTime) {
        bool isNotConnected = result != ConnectivityResult.wifi && result != ConnectivityResult.mobile;
        isNotConnected ? const SizedBox() : ScaffoldMessenger.of(context).hideCurrentSnackBar();
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          backgroundColor: isNotConnected ? Colors.red : Colors.green,
          duration: Duration(seconds: isNotConnected ? 6000 : 3),
          content: Text(isNotConnected ? 'no_connection' : 'connected',
            textAlign: TextAlign.center)));
        if(!isNotConnected) {
          _route();
        }
      }
      _firstTime = false;
    });
    Get.find<SplashController>().initSharedData();
    _route();
  }

  @override
  void dispose() {
    super.dispose();
    _onConnectivityChanged.cancel();
  }

  void _route() {
    Get.find<SplashController>().getConfigData().then((isSuccess) async {
      await Get.find<ProfileController>().getProfile();
      if(isSuccess) {
        Get.find<SplashController>().getBusinessPagesList('default');
        final config = Get.find<SplashController>().configModel;
        Timer(const Duration(seconds: 1), () async {
          if( config?.maintenanceModeData?.maintenanceStatus == 1 && config?.maintenanceModeData?.selectedMaintenanceSystem?.deliverymanApp == 1) {
            Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(
              builder: (_) => const MaintenanceScreen(),
              settings: const RouteSettings(name: 'MaintenanceScreen'),
            ));
          }else{
            if(widget.body != null) {
              String notificationType = widget.body?.type??"";
              switch(notificationType.toLowerCase()) {
                case 'chatting' : {
                  Get.offAll(DashboardScreen(pageIndex: 2, chatIndex: widget.body?.messageKey == 'message_from_customer' ? 1 : widget.body?.messageKey == 'message_from_seller' ? 0 : 3));
                }
                break;

                case 'theme' : {
                  Get.offAll(const NotificationScreen(fromNotification: true));
                }
                break;

                case 'order' : {
                  Get.offAll(OrderDetailsScreen(orderModel: OrderModel(id:  widget.body?.orderId), fromNotification: true));
                }
                break;

                case 'wallet' : {
                  if(widget.body?.type  == 'wallet' && widget.body?.messageKey  == 'cash_collect_by_seller_message'){
                    Get.offAll(() => WalletScreen(fromNotification: true, selectedIndex:  widget.body?.messageKey == 'cash_collect_by_seller_message' ? 3 : 0));
                  }else{
                    Get.offAll(() => WalletScreen(fromNotification: true, selectedIndex:  widget.body?.messageKey == 'cash_collect_by_admin_message' ? 3 : 0));
                  }
                }
                break;

                case 'wallet_withdraw' : {
                  Get.offAll(() => WalletScreen(fromNotification: true, selectedIndex:  widget.body?.messageKey == 'withdraw_request_status_message' ? 1 : 0));
                }
                break;

                default: {
                  Get.offAll(const NotificationScreen(fromNotification: true));
                } break;
              }

            } else {
              if (Get.find<AuthController>().isLoggedIn()) {
                Get.find<AuthController>().updateToken();
                await Get.find<ProfileController>().getProfile();
                Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => const DashboardScreen(pageIndex: 0)));
              } else {
                if (Get.find<SplashController>().showIntro()!) {
                  Get.offAll(const OnBoardingScreen());
                } else {
                  Get.offAll(const LoginScreen());
                }
              }
            }
          }





        });
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      key: _globalKey,

      body: Center(child: Padding(padding:  EdgeInsets.all(Dimensions.paddingSizeLarge),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            Image.asset(Images.splashLogo, width: Dimensions.splashLogoWidth),
             SizedBox(height: Dimensions.paddingSizeDefault),
            Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                Text(AppConstants.appName,
                    style: rubikMedium.copyWith(fontSize: Dimensions.fontSizeOverLarge), textAlign: TextAlign.center),
                 SizedBox(width: Dimensions.fontSizeExtraSmall),
                Text('APP', style: rubikMedium.copyWith(fontSize: Dimensions.fontSizeOverLarge,
                    color: Theme.of(context).primaryColor), textAlign: TextAlign.center)]),
          ]))),
    );
  }
}