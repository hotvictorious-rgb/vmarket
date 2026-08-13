import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/features/notification/domain/models/notification_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/notification/domain/services/notification_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/helper/api_checker.dart';

class NotificationController extends ChangeNotifier {
  final NotificationServiceInterface notificationServiceInterface;

  NotificationController({required this.notificationServiceInterface});

  NotificationItemModel? notificationModel;


  Future<void> getNotificationList(int offset) async {
    ApiResponseModel apiResponse = await notificationServiceInterface.getList(offset : offset);
    if (apiResponse.response != null && apiResponse.response?.statusCode == 200) {
      if(offset == 1){
        notificationModel = NotificationItemModel.fromJson(apiResponse.response?.data);
      }else{
        notificationModel?.notification?.addAll(NotificationItemModel.fromJson(apiResponse.response?.data).notification!);
        notificationModel?.offset = NotificationItemModel.fromJson(apiResponse.response?.data).offset!;
        notificationModel?.totalSize = NotificationItemModel.fromJson(apiResponse.response?.data).totalSize!;
      }
    } else {
      ApiChecker.checkApi( apiResponse);
    }
    notifyListeners();
  }



  int getUnreadNotificationCount() {
    if (notificationModel?.notification == null || notificationModel!.notification!.isEmpty) {
      return 0;
    }
    return notificationModel!.notification!.where((item) => item.seen == null).length;
  }

  Future<void> seenNotification(int id) async {
    ApiResponseModel apiResponse = await notificationServiceInterface.seenNotification(id);
    if (apiResponse.response != null && apiResponse.response?.statusCode == 200) {
      // Mark locally first for instantaneous UI update
      if (notificationModel?.notification != null) {
        for (var item in notificationModel!.notification!) {
          if (item.id == id) {
            item.seen = NotificationSeenBy(id: 0, userId: 0, notificationId: id);
          }
        }
      }
      getNotificationList(1);
    }
    notifyListeners();
  }
}
