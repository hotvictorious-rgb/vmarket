
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/features/chat/screens/conversation_screen.dart';
import 'package:sixvalley_delivery_boy/features/home/screens/home_screen.dart';
import 'package:sixvalley_delivery_boy/features/notification/screens/notification_screen.dart';
import 'package:sixvalley_delivery_boy/features/order/screens/order_history_screen.dart';
import 'package:sixvalley_delivery_boy/features/profile/screens/profile_screen.dart';

class DashboardController extends GetxController implements GetxService{
  int _currentTab = 0;
  int get currentTab => _currentTab;
  late List<Widget> screen;
  Widget? _currentScreen;
  Widget? get currentScreen => _currentScreen;
  DashboardController() {
    initPage();
  }


  void selectHomePage({bool first = true}) {
    _currentScreen = screen[0];
    _currentTab = 0;
    if(first){
      update();
    }
  }


  void initPage() {
    screen = [
      HomeScreen(onTap: (int index) {
        _currentTab = index;
        update();
      }),
      const OrderHistoryScreen(fromMenu: true),
      const ConversationScreen(fromNotification: false),
      const NotificationScreen(fromNotification: false),
      const ProfileScreen(),
    ];
    _currentScreen = screen[0];
  }



  void selectOrderHistoryScreen({bool fromHome =  false}) {
    _currentScreen = OrderHistoryScreen(fromMenu: !fromHome);
    _currentTab = 1;
    update();
  }


  void selectConversationScreen({bool isUpdate = true, int? chatIndex}) {
    _currentScreen = ConversationScreen(fromNotification: !isUpdate, chatIndex: chatIndex);
    _currentTab = 2;
    if(isUpdate){
      update();
    }
  }


  void selectNotificationScreen({bool isUpdate = true}) {
    _currentScreen = NotificationScreen(fromNotification: !isUpdate);
    _currentTab = 3;
    if(isUpdate) {
      update();
    }
  }


  void selectProfileScreen({bool isUpdate = true}) {
    _currentScreen = const ProfileScreen();
    _currentTab = 4;
    if(isUpdate){
      update();
    }
  }
}
