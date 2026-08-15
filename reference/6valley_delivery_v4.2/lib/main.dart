import 'dart:async';
import 'dart:io';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_downloader/flutter_downloader.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/features/notification/domain/models/notification_body.dart';
import 'package:sixvalley_delivery_boy/theme/dark_theme.dart';
import 'package:sixvalley_delivery_boy/theme/light_theme.dart';
import 'package:sixvalley_delivery_boy/utill/app_constants.dart';
import 'package:sixvalley_delivery_boy/utill/messages.dart';
import 'package:sixvalley_delivery_boy/features/splash/screens/splash_screen.dart';
import 'common/controllers/localization_controller.dart';
import 'features/splash/controllers/splash_controller.dart';
import 'theme/controllers/theme_controller.dart';
import 'helper/get_di.dart' as di;
import 'package:url_strategy/url_strategy.dart';
import 'helper/notification_helper.dart';


final FlutterLocalNotificationsPlugin flutterLocalNotificationsPlugin = FlutterLocalNotificationsPlugin();
late AndroidNotificationChannel channel;


Future<void> main() async {
  HttpOverrides.global = MyHttpOverrides();

  setPathUrlStrategy();
  WidgetsFlutterBinding.ensureInitialized();

  await FlutterDownloader.initialize(debug: true, ignoreSsl: true);


  if(Firebase.apps.isEmpty){
    if(Platform.isAndroid){
      try{
        await Firebase.initializeApp(options: const FirebaseOptions(
            apiKey: "current_key here",
            projectId: "project_id here",
            messagingSenderId: "project_number here",
            appId: "mobilesdk_app_id here"
        ));
      } finally{
        await Firebase.initializeApp();
      }

    }else{
      await Firebase.initializeApp();
    }
  }



  if(defaultTargetPlatform == TargetPlatform.android) {
    await FirebaseMessaging.instance.requestPermission();
  }



  Map<String, Map<String, String>> _languages = await di.init();


  NotificationBody? body;

  try {
    channel = const AndroidNotificationChannel(
      'high_importance_channel',
      'High Importance Notifications',
      importance: Importance.high,
    );
    final RemoteMessage? remoteMessage = await FirebaseMessaging.instance.getInitialMessage();
    if (remoteMessage != null) {
      body = NotificationBody.fromJson(remoteMessage.data);
    }
    await NotificationHelper.initialize(flutterLocalNotificationsPlugin);
    FirebaseMessaging.onBackgroundMessage(myBackgroundMessageHandler);
    await flutterLocalNotificationsPlugin.resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()?.createNotificationChannel(channel);
  }catch(_) {}


  runApp(MyApp(languages: _languages, body: body));
}

class MyApp extends StatelessWidget {
  final Map<String, Map<String, String>> languages;
  final NotificationBody? body;
  const MyApp({Key? key, required this.languages, this.body}) : super(key: key);
  @override
  Widget build(BuildContext context) {

    return GetBuilder<ThemeController>(builder: (themeController) {
      return GetBuilder<LocalizationController>(builder: (localizeController) {
        return GetBuilder<SplashController>(builder: (splashController) {
          return  GetMaterialApp(

            title: AppConstants.appName,
            debugShowCheckedModeBanner: false,
            navigatorKey: Get.key,
            theme: themeController.darkTheme ? dark : light,
            locale: localizeController.locale,
            translations: Messages(languages: languages),
            fallbackLocale: Locale(AppConstants.languages[0].languageCode!, AppConstants.languages[0].countryCode),
            home: SplashScreen(body: body),
            defaultTransition: Transition.topLevel,
            transitionDuration: const Duration(milliseconds: 500),
              builder:(context,child){
                return MediaQuery(data: MediaQuery.of(context).copyWith(textScaler: TextScaler.noScaling), child: SafeArea(top: false, child: child!));
              }
          );
        });
      });
    });
  }
}


class MyHttpOverrides extends HttpOverrides {
  @override
  HttpClient createHttpClient(SecurityContext? context) {
    return super.createHttpClient(context)..badCertificateCallback = (X509Certificate cert, String host, int port) => true;
  }
}