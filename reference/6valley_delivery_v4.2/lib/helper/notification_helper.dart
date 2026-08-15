import 'dart:convert';
import 'dart:io';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:get/get.dart';
import 'package:path_provider/path_provider.dart';
import 'package:http/http.dart' as http;
import 'package:sixvalley_delivery_boy/features/auth/controllers/auth_controller.dart';
import 'package:sixvalley_delivery_boy/features/auth/screens/login_screen.dart';
import 'package:sixvalley_delivery_boy/features/dashboard/screens/dashboard_screen.dart';
import 'package:sixvalley_delivery_boy/features/maintenance/maintenance_screen.dart';
import 'package:sixvalley_delivery_boy/features/notification/domain/models/notification_body.dart';
import 'package:sixvalley_delivery_boy/features/notification/screens/notification_screen.dart';
import 'package:sixvalley_delivery_boy/features/order/controllers/order_controller.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/models/order_model.dart';
import 'package:sixvalley_delivery_boy/features/order_details/screens/order_details_screen.dart';
import 'package:sixvalley_delivery_boy/features/splash/controllers/splash_controller.dart';
import 'package:sixvalley_delivery_boy/features/splash/domain/models/config_model.dart';
import 'package:sixvalley_delivery_boy/features/wallet/screens/wallet_screen.dart';
import 'package:sixvalley_delivery_boy/utill/app_constants.dart';

class NotificationHelper {

  static Future<void> initialize(FlutterLocalNotificationsPlugin flutterLocalNotificationsPlugin) async {
    var androidInitialize = const AndroidInitializationSettings('notification_icon');
    var iOSInitialize = const DarwinInitializationSettings();
    var initializationsSettings = InitializationSettings(android: androidInitialize, iOS: iOSInitialize);

    flutterLocalNotificationsPlugin.initialize(initializationsSettings, onDidReceiveNotificationResponse: (NotificationResponse data) async {

      try{
        NotificationBody payload;
        if(data.payload != null && data.payload!.isNotEmpty) {
          payload = NotificationBody.fromJson(jsonDecode(data.payload!));
          if(payload.type == 'chatting') {
            Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(builder: (_) => DashboardScreen(pageIndex: 2, chatIndex: payload.messageKey == 'message_from_customer' ? 1 : payload.messageKey == 'message_from_seller' ? 0 : 3)));
          } else if(payload.type == 'Theme') {
            Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(builder: (_) => const NotificationScreen(fromNotification: true)));
          } else if(payload.type == 'order') {
            Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(builder: (_) => OrderDetailsScreen(orderModel: OrderModel(id: payload.orderId), fromNotification: true)));
          } else if(payload.type  == 'wallet_withdraw') {
            Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(builder: (_) =>  WalletScreen(fromNotification: true, selectedIndex:  payload.messageKey == 'withdraw_request_status_message' ? 1 : 0 )));
          } else if(payload.type  == 'wallet' && payload.messageKey == 'cash_collect_by_seller_message'){
            Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(builder: (_) =>  WalletScreen(fromNotification: true, selectedIndex:  payload.messageKey == 'cash_collect_by_seller_message' ? 3 : 0)));
          } else if(payload.type  == 'wallet'){
            Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(builder: (_) =>  WalletScreen(fromNotification: true, selectedIndex:  payload.messageKey == 'cash_collect_by_admin_message' ? 3 : 0)));
          } else {
            Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(builder: (_) => const NotificationScreen(fromNotification: true)));
          }
        }
      }catch (e) {
        if (kDebugMode) {
          print(e);
        }
      }
      return;
    });


    FirebaseMessaging.onMessage.listen((RemoteMessage message) async {
      try{
        // NotificationBody payload;
        if(message.data.isNotEmpty) {
         //  payload = NotificationBody.fromJson(jsonDecode(message.data));
        }
      } catch (e) {
        if (kDebugMode) {
          print(e);
        }
      }

      if(message.data['type'] == 'maintenance_mode') {
        final SplashController splashProvider = Get.find<SplashController>();
        await splashProvider.getConfigData();

        ConfigModel? config = Get.find<SplashController>().configModel;


        if(config?.maintenanceModeData?.maintenanceStatus == 1 && (config?.maintenanceModeData?.selectedMaintenanceSystem?.deliverymanApp == 1)) {
          Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(
            builder: (_) => const MaintenanceScreen(),
            settings: const RouteSettings(name: 'MaintenanceScreen'),
          ));
        }else if (config?.maintenanceModeData?.maintenanceStatus == 0 && Get.currentRoute == 'MaintenanceScreen' ) {
          final AuthController authController = Get.find<AuthController>();
          if(authController.isLoggedIn()){
            Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(builder: (_) => const DashboardScreen(pageIndex: 0)));
          }else {
            Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(builder: (_) => const LoginScreen()));
          }
        }
      }

      if(message.data['type'] != 'maintenance_mode') {
        showNotification(message, flutterLocalNotificationsPlugin, kIsWeb);
      }
    });

    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) async {
      debugPrint("onMessageOpenedApp: ${message.data}");
      Get.find<OrderController>().getCurrentOrders();
      NotificationBody? payload;
      if(message.data.isNotEmpty) {
        payload = NotificationBody.fromJson(message.data);
      }

      if(payload?.type == 'chatting') {
        Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(builder: (_) => DashboardScreen(pageIndex: 2, chatIndex: payload?.messageKey == 'message_from_customer' ? 1 : payload?.messageKey == 'message_from_seller' ? 0 : 3)));
      } else if(payload?.type == 'Theme') {
        Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(builder: (_) => const NotificationScreen(fromNotification: true)));
      } else if(payload?.type == 'order'){
        Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(builder: (_) => OrderDetailsScreen(orderModel: OrderModel(id: payload?.orderId), fromNotification: true)));
      } else if(payload?.type  == 'wallet_withdraw'){
        Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(builder: (_) =>  WalletScreen(fromNotification: true, selectedIndex:  payload?.messageKey == 'withdraw_request_status_message' ? 1 : 0 )));
      } else if(payload?.type  == 'wallet' && payload?.messageKey == 'cash_collect_by_seller_message'){
        Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(builder: (_) =>  WalletScreen(fromNotification: true, selectedIndex:  payload?.messageKey == 'cash_collect_by_seller_message' ? 3 : 0)));
      } else if(payload?.type  == 'wallet'){
        Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(builder: (_) =>  WalletScreen(fromNotification: true, selectedIndex:  payload?.messageKey == 'cash_collect_by_admin_message' ? 3 : 0)));
      }else {
        Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(builder: (_) => const NotificationScreen(fromNotification: true)));
      }

      if(message.data['type'] == 'maintenance_mode') {
        final SplashController splashProvider = Get.find<SplashController>();
        await splashProvider.getConfigData();

        ConfigModel? config = Get.find<SplashController>().configModel;


        if(config?.maintenanceModeData?.maintenanceStatus == 1 && (config?.maintenanceModeData?.selectedMaintenanceSystem?.deliverymanApp == 1)) {
          Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(
            builder: (_) => const MaintenanceScreen(),
            settings: const RouteSettings(name: 'MaintenanceScreen'),
          ));
        }else if (config?.maintenanceModeData?.maintenanceStatus == 0 && Get.currentRoute == 'MaintenanceScreen') {
          final AuthController authController = Get.find<AuthController>();
          if(authController.isLoggedIn()) {
            Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(builder: (_) => const DashboardScreen(pageIndex: 0)));
          } else {
            Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(builder: (_) => const LoginScreen()));
          }
        }
      }

    });
  }

  static Future<void> showNotification(RemoteMessage message, FlutterLocalNotificationsPlugin? fln, bool data,{ bool notificationSound = true}) async {
    String? _title;
    String? _body;
    String? _orderID;
    String? _image;

    _title = message.data['title'];
    _body = message.data['body'];
    _orderID = message.data['order_id'];
    _image = (message.data['image'] != null && message.data['image'].isNotEmpty)
        ? message.data['image'].startsWith('http') ? message.data['image']
        : '${AppConstants.baseUri}/storage/app/public/notification/${message.data['image']}' : null;


    if(_image != null && _image.isNotEmpty) {
      try{
        await showBigPictureNotificationHiddenLargeIcon(_title, _body ?? '', _orderID, _image, fln!);
      }catch(e) {
        await showBigTextNotification(_title, _body ?? '', message.data, _orderID, fln!);
      }
    }else {
      await showBigTextNotification(_title, _body ?? '', message.data, _orderID, fln!);
    }
  }

  static Future<void> showTextNotification(String title, String body, String orderID, FlutterLocalNotificationsPlugin fln, {bool playSound = true}) async {
    const AndroidNotificationDetails androidPlatformChannelSpecifics = AndroidNotificationDetails(
      '6valley_delivery', '6valley_delivery name', playSound:  true,
      importance: Importance.max, priority: Priority.max, sound: RawResourceAndroidNotificationSound('notification'),
    );
    const NotificationDetails platformChannelSpecifics = NotificationDetails(android: androidPlatformChannelSpecifics);
    await fln.show(0, title, body, platformChannelSpecifics, payload: orderID);
  }

  static Future<void> showBigTextNotification(String? title, String body, Map<String, dynamic> data, String? orderID, FlutterLocalNotificationsPlugin fln, {bool playSound = true}) async {
    BigTextStyleInformation bigTextStyleInformation = BigTextStyleInformation(
      body, htmlFormatBigText: true,
      contentTitle: title, htmlFormatContentTitle: true,
    );
    AndroidNotificationDetails androidPlatformChannelSpecifics = AndroidNotificationDetails(
      '6valley_delivery channel id', '6valley_delivery name', importance: Importance.max,
      styleInformation: bigTextStyleInformation, priority: Priority.max, playSound: true,
      sound: const RawResourceAndroidNotificationSound('notification'),
    );
    NotificationDetails platformChannelSpecifics = NotificationDetails(android: androidPlatformChannelSpecifics);
    await fln.show(0, title, body, platformChannelSpecifics, payload: jsonEncode(data));
  }

  static Future<void> showBigPictureNotificationHiddenLargeIcon(String? title, String? body, String? orderID, String image, FlutterLocalNotificationsPlugin fln,{bool playSound = true}) async {
    final String largeIconPath = await _downloadAndSaveFile(image, 'largeIcon');
    final String bigPicturePath = await _downloadAndSaveFile(image, 'bigPicture');
    final BigPictureStyleInformation bigPictureStyleInformation = BigPictureStyleInformation(
      FilePathAndroidBitmap(bigPicturePath), hideExpandedLargeIcon: true,
      contentTitle: title, htmlFormatContentTitle: true,
      summaryText: body, htmlFormatSummaryText: true,
    );
    final AndroidNotificationDetails androidPlatformChannelSpecifics = AndroidNotificationDetails(
      '6valley_delivery', '6valley_delivery name',
      largeIcon: FilePathAndroidBitmap(largeIconPath), priority: Priority.max, playSound: playSound? true: false,
      styleInformation: bigPictureStyleInformation, importance: Importance.max,
      sound: const RawResourceAndroidNotificationSound('notification'),
    );
    final NotificationDetails platformChannelSpecifics = NotificationDetails(android: androidPlatformChannelSpecifics);
    await fln.show(0, title, body, platformChannelSpecifics, payload: orderID);
  }

  static Future<String> _downloadAndSaveFile(String url, String fileName) async {
    final Directory directory = await getApplicationDocumentsDirectory();
    final String filePath = '${directory.path}/$fileName';
    final http.Response response = await http.get(Uri.parse(url));
    final File file = File(filePath);
    await file.writeAsBytes(response.bodyBytes);
    return filePath;
  }

}

Future<dynamic> myBackgroundMessageHandler(RemoteMessage message) async {
  debugPrint("onBackground: ${message.notification?.title}/${message.notification?.body}/${message.notification?.titleLocKey}");
  // var androidInitialize = const AndroidInitializationSettings('notification_icon');
  // var iOSInitialize = const IOSInitializationSettings();
  // var initializationsSettings = InitializationSettings(android: androidInitialize, iOS: iOSInitialize);
  // FlutterLocalNotificationsPlugin flutterLocalNotificationsPlugin = FlutterLocalNotificationsPlugin();
  // flutterLocalNotificationsPlugin.initialize(initializationsSettings);
  // NotificationHelper.showNotification(message, flutterLocalNotificationsPlugin, true);
}