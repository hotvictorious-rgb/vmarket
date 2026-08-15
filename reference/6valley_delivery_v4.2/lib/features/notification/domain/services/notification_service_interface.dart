
abstract class NotificationServiceInterface {

  Future<dynamic> getNotificationList(int offset);
  void saveSeenNotificationCount(int count);
  int? getSeenNotificationCount();

}