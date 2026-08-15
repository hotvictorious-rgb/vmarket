import 'package:get/get_connect/http/src/response/response.dart';
import 'package:sixvalley_delivery_boy/features/notification/domain/repositories/notification_repository_interface.dart';
import 'package:sixvalley_delivery_boy/features/notification/domain/services/notification_service_interface.dart';

class NotificationService implements NotificationServiceInterface{
  NotificationRepositoryInterface notificationRepoInterfcace;
  NotificationService({required this.notificationRepoInterfcace});

  @override
  Future<Response> getNotificationList(int offset) {
    return notificationRepoInterfcace.getNotificationList(offset);
  }

  @override
  int? getSeenNotificationCount() {
    return notificationRepoInterfcace.getSeenNotificationCount();
  }

  @override
  void saveSeenNotificationCount(int count) {
    return saveSeenNotificationCount(count);
  }

}