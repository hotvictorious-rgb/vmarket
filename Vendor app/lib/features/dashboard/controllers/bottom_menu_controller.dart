import 'package:flutter/material.dart';
import 'package:sixvalley_vendor_app/features/home/screens/home_page_screen.dart';
import 'package:sixvalley_vendor_app/features/order/screens/order_screen.dart';
import 'package:sixvalley_vendor_app/features/product/screens/product_list_screen.dart';

class BottomMenuController extends ChangeNotifier {
  int _currentTab = 0;
  int get currentTab => _currentTab;
  final List<Widget> screen = [
    const HomePageScreen(),
    const OrderScreen(),
    const ProductListMenuScreen(),
  ];
  Widget _currentScreen = const HomePageScreen();
  Widget get currentScreen => _currentScreen;

  void resetNavBar() {
    _currentScreen = const HomePageScreen();
    _currentTab = 0;
  }

  void selectHomePage() {
    _currentScreen = const HomePageScreen();
    _currentTab = 0;
    notifyListeners();
  }

  void selectOrderScreen() {
    _currentScreen = const OrderScreen();
    _currentTab = 1;
    notifyListeners();
  }

  void selectItemsScreen() {
    _currentScreen = const ProductListMenuScreen();
    _currentTab = 2;
    notifyListeners();
  }
}
