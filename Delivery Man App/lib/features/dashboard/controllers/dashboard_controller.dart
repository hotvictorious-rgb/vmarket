import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/features/home/screens/home_screen.dart';
import 'package:sixvalley_delivery_boy/features/order/screens/order_history_screen.dart';
import 'package:sixvalley_delivery_boy/features/profile/screens/profile_screen.dart';
import 'package:sixvalley_delivery_boy/features/wallet/screens/wallet_screen.dart';

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
      const WalletScreen(fromNotification: false, fromMenu: true),
      const ProfileScreen(),
    ];
    _currentScreen = screen[0];
  }



  void selectOrderHistoryScreen({bool fromHome =  false}) {
    _currentScreen = OrderHistoryScreen(fromMenu: !fromHome);
    _currentTab = 1;
    update();
  }

  void selectEarningsScreen() {
    _currentScreen = const WalletScreen(fromNotification: false, fromMenu: true);
    _currentTab = 2;
    update();
  }


  void selectProfileScreen({bool isUpdate = true}) {
    _currentScreen = const ProfileScreen();
    _currentTab = 3;
    if(isUpdate){
      update();
    }
  }
}
