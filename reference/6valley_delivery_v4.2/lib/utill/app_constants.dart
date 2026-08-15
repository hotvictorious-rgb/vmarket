import 'package:sixvalley_delivery_boy/features/language/domain/models/language_model.dart';
import 'images.dart';

class AppConstants {
  static const String companyName = '6valley';
  static const String appName = 'Deliveryman';
  static const String appVersion = '4.2'; ///flutter SDK : 3.29.3
  static const String polylineMapKey = 'YOUR_MAP_KEY_HERE';

  static const String baseUri = 'YOUR_BASE_URL_HERE';

  static const String profileUri = '/api/v2/delivery-man/info';
  static const String configUri = '/api/v1/config';
  static const String loginUri = '/api/v2/delivery-man/auth/login';
  static const String notificationUri = '/api/v2/delivery-man/notifications';
  static const String currentOrderUri = '/api/v2/delivery-man/current-orders';
  static const String orderDetailsUri = '/api/v2/delivery-man/order-details?order_id=';
  static const String allOrderHistoryUri = '/api/v2/delivery-man/all-orders';
  static const String recordLocationUri = '/api/v2/delivery-man/record-location-data';
  static const String updateOrderStatusUri = '/api/v2/delivery-man/update-order-status';
  static const String rescheduleOrderStatusUri = '/api/v2/delivery-man/update-expected-delivery';
  static const String pauseAndResumeOrderStatusUri = '/api/v2/delivery-man/order-update-is-pause';
  static const String updatePaymentStatusUri = '/api/v2/delivery-man/update-payment-status';
  static const String tokenUri = '/api/v2/delivery-man/update-fcm-token';
  static const String searchConversationListUri = '/api/v2/delivery-man/update-fcm-token';
  static const String statusOnOffUri = '/api/v2/delivery-man/is-online';
  static const String withdrawRequestUri = '/api/v2/delivery-man/withdraw-request';
  static const String walletInfoUri = '/api/v2/delivery-man/wallet';
  static const String orderCountUri = '/api/v2/delivery-man/order-count';
  static const String deliveryWiseEarnedUri = '/api/v2/delivery-man/delivery-wise-earned';
  static const String orderListFilterByDate = '/api/v2/delivery-man/order-list-by-date';
  static const String orderSearchUri = '/api/v2/delivery-man/search';
  static const String profileUpdateUri = '/api/v2/delivery-man/update-info';
  static const String chatListUri = '/api/v2/delivery-man/messages/list/';
  static const String messageListUri = '/api/v2/delivery-man/messages/get-message/';
  static const String sendMessageUri = '/api/v2/delivery-man/messages/send-message/';
  static const String withdrawListUri = '/api/v2/delivery-man/withdraw-list-by-approved';
  static const String emergencyContactList = '/api/v2/delivery-man/emergency-contact-list';
  static const String depositedList = '/api/v2/delivery-man/collected_cash_history';
  static const String forgotPassword = '/api/v2/delivery-man/auth/forgot-password';
  static const String verifyOtp = '/api/v2/delivery-man/auth/verify-otp';
  static const String resetPassword = '/api/v2/delivery-man/auth/reset-password';
  static const String reviewListUri = '/api/v2/delivery-man/review-list';
  static const String updateBankInfo = '/api/v2/delivery-man/bank-info';
  static const String distanceApi = '/api/v2/delivery-man/distance-api';
  static const String chatSearch = '/api/v2/delivery-man/messages/search/';
  static const String addToSavedReviewList = '/api/v2/delivery-man/save-review';
  static const String deliveryVerificationImage = '/api/v2/delivery-man/order-delivery-verification';
  static const String otpVerificationForOrder = '/api/v2/delivery-man/verify-order-delivery-otp';
  static const String resendVerificationCode = '/api/v2/delivery-man/resend-verification-code';
  static const String setCurrentLanguageUri = '/api/v2/delivery-man/language-change';
  static const String singleOrderHistoryUri = '/api/v2/delivery-man/order-item';
  static const String businessPagesUri = '/api/v1/business-pages?type=';


  // Shared Key
  static const String theme = 'theme';
  static const String token = 'token';
  static const String countryCode = 'country_code';
  static const String languageCode = 'language_code';
  static const String cartList = 'cart_list';
  static const String userPassword = 'user_password';
  static const String userEmail = 'user_email';
  static const String currency = 'currency';
  static const String topic = 'six_valley_delivery';
  static const String maintenanceModeTopic = 'maintenance_mode_start_deliveryman';
  static const String intro = '6valley_delivery';
  static const String localizationKey = 'X-localization';
  static const String notificationCount = 'count';
  static const String notificationSound = 'sound';
  static const String userCountryCode = 'user_country_code';


  static List<LanguageModel> languages = [
    LanguageModel(imageUrl: Images.unitedKindom, languageName: 'English', countryCode: 'US', languageCode: 'en'),
    LanguageModel(imageUrl: Images.arabic, languageName: 'Arabic', countryCode: 'SA', languageCode: 'ar'),
    LanguageModel(imageUrl: Images.hindi, languageName: 'Hindi', countryCode: 'IN', languageCode: 'hi'),
    LanguageModel(imageUrl: Images.bd, languageName: 'Bangla', countryCode: 'BD', languageCode: 'bn'),
    LanguageModel(imageUrl: Images.spanish, languageName: 'Spanish', countryCode: 'ES', languageCode: 'es'),
  ];

  static const int limitOfPickedIdentityImageNumber = 2;
  static const double limitOfPickedImageSizeInMB = 2;
  static const double balanceInputLength = 10;

  static const double maxLimitOfFileSentINConversation = 25;
  static const double maxLimitOfTotalFileSent = 5;
  static const double maxSizeOfASingleFile = 10;



  static const List<String> videoExtensions = [
    'mp4', 'mkv', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mpeg', 'mpg', 'm4v', '3gp', 'ogv'
  ];

  static const List<String> imageExtensions = [
    'jpg', 'jpeg', 'jpe', 'jif', 'jfif', 'jfi', 'png', 'gif', 'webp', 'tiff', 'tif', 'bmp', 'svg',
  ];

  static const List<String> documentExtensions = [
    'doc', 'docx', 'txt', 'csv', 'xls', 'xlsx', 'rar', 'tar', 'targz', 'zip', 'pdf',

  ];
}
