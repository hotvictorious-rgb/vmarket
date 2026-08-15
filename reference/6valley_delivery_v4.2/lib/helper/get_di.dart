import 'dart:convert';
import 'package:flutter/services.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/features/auth/controllers/auth_controller.dart';
import 'package:sixvalley_delivery_boy/features/auth/domain/repositories/auth_repository_interface.dart';
import 'package:sixvalley_delivery_boy/features/auth/domain/services/auth_service.dart';
import 'package:sixvalley_delivery_boy/features/auth/domain/services/auth_service_interface.dart';
import 'package:sixvalley_delivery_boy/features/chat/controllers/chat_controller.dart';
import 'package:sixvalley_delivery_boy/features/chat/domain/repositories/chat_repository_interface.dart';
import 'package:sixvalley_delivery_boy/features/chat/domain/services/chat_service.dart';
import 'package:sixvalley_delivery_boy/features/chat/domain/services/chat_service_interface.dart';
import 'package:sixvalley_delivery_boy/features/emergency_contact/controllers/emergency_contruct_controller.dart';
import 'package:sixvalley_delivery_boy/features/emergency_contact/domain/repositories/emergency_contruct_repository.dart';
import 'package:sixvalley_delivery_boy/features/emergency_contact/domain/repositories/emergency_contruct_repository_interface.dart';
import 'package:sixvalley_delivery_boy/features/emergency_contact/domain/services/emergency_contruct_service.dart';
import 'package:sixvalley_delivery_boy/features/emergency_contact/domain/services/emergency_contruct_service_interface.dart';
import 'package:sixvalley_delivery_boy/features/language/controllers/language_controller.dart';
import 'package:sixvalley_delivery_boy/common/controllers/localization_controller.dart';
import 'package:sixvalley_delivery_boy/features/dashboard/controllers/dashboard_controller.dart';
import 'package:sixvalley_delivery_boy/features/live_tracking/controllers/rider_controller.dart';
import 'package:sixvalley_delivery_boy/features/notification/controllers/notification_controller.dart';
import 'package:sixvalley_delivery_boy/features/notification/domain/repositories/notification_repository_interface.dart';
import 'package:sixvalley_delivery_boy/features/notification/domain/services/notification_service.dart';
import 'package:sixvalley_delivery_boy/features/notification/domain/services/notification_service_interface.dart';
import 'package:sixvalley_delivery_boy/features/onboard/controllers/onboarding_controller.dart';
import 'package:sixvalley_delivery_boy/features/onboard/domain/repositories/onbording_repository_interface.dart';
import 'package:sixvalley_delivery_boy/features/onboard/domain/services/onboard_service.dart';
import 'package:sixvalley_delivery_boy/features/onboard/domain/services/onboard_service_interface.dart';
import 'package:sixvalley_delivery_boy/features/order/controllers/order_controller.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/repositories/order_repository_interface.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/services/order_service.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/services/order_service_interface.dart';
import 'package:sixvalley_delivery_boy/features/order_details/controllers/order_details_controller.dart';
import 'package:sixvalley_delivery_boy/features/order_details/domain/repositories/order_details_repository.dart';
import 'package:sixvalley_delivery_boy/features/order_details/domain/repositories/order_details_repository_interface.dart';
import 'package:sixvalley_delivery_boy/features/order_details/domain/services/order_details_service.dart';
import 'package:sixvalley_delivery_boy/features/order_details/domain/services/order_details_service_interface.dart';
import 'package:sixvalley_delivery_boy/features/profile/controllers/profile_controller.dart';
import 'package:sixvalley_delivery_boy/features/profile/domain/repositories/profile_repository_interface.dart';
import 'package:sixvalley_delivery_boy/features/profile/domain/services/profile_service.dart';
import 'package:sixvalley_delivery_boy/features/profile/domain/services/profile_service_interface.dart';
import 'package:sixvalley_delivery_boy/features/review/controllers/revice_controller.dart';
import 'package:sixvalley_delivery_boy/features/review/domain/repositories/review_repository.dart';
import 'package:sixvalley_delivery_boy/features/review/domain/repositories/review_repository_interface.dart';
import 'package:sixvalley_delivery_boy/features/review/domain/services/review_service.dart';
import 'package:sixvalley_delivery_boy/features/review/domain/services/review_service_interface.dart';
import 'package:sixvalley_delivery_boy/features/splash/controllers/splash_controller.dart';
import 'package:sixvalley_delivery_boy/features/splash/domain/repositories/splash_repository_interface.dart';
import 'package:sixvalley_delivery_boy/features/splash/domain/services/splash_service.dart';
import 'package:sixvalley_delivery_boy/features/splash/domain/services/splash_service_interface.dart';
import 'package:sixvalley_delivery_boy/features/wallet/domain/repositories/wallet_repository_interface.dart';
import 'package:sixvalley_delivery_boy/features/wallet/domain/services/wallet_service.dart';
import 'package:sixvalley_delivery_boy/features/wallet/domain/services/wallet_service_interface.dart';
import 'package:sixvalley_delivery_boy/features/withdraw/controllers/withdraw_controller.dart';
import 'package:sixvalley_delivery_boy/features/withdraw/domain/repositories/withdraw_repository.dart';
import 'package:sixvalley_delivery_boy/features/withdraw/domain/repositories/withdraw_repository_interfaec.dart';
import 'package:sixvalley_delivery_boy/features/withdraw/domain/services/withdraw_service.dart';
import 'package:sixvalley_delivery_boy/features/withdraw/domain/services/withdraw_service_interface.dart';
import 'package:sixvalley_delivery_boy/theme/controllers/theme_controller.dart';
import 'package:sixvalley_delivery_boy/features/wallet/controllers/wallet_controller.dart';
import 'package:sixvalley_delivery_boy/data/api/api_client.dart';
import 'package:sixvalley_delivery_boy/features/language/domain/models/language_model.dart';
import 'package:sixvalley_delivery_boy/data/repository/rider_repository.dart';
import 'package:sixvalley_delivery_boy/features/auth/domain/repositories/auth_repository.dart';
import 'package:sixvalley_delivery_boy/features/chat/domain/repositories/chat_repository.dart';
import 'package:sixvalley_delivery_boy/features/language/domain/repositories/language_repository.dart';
import 'package:sixvalley_delivery_boy/features/notification/domain/repositories/notification_repository.dart';
import 'package:sixvalley_delivery_boy/features/onboard/domain/repositories/onboarding_repository.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/repositories/order_repository.dart';
import 'package:sixvalley_delivery_boy/features/profile/domain/repositories/profile_repository.dart';
import 'package:sixvalley_delivery_boy/features/splash/domain/repositories/splash_repository.dart';
import 'package:sixvalley_delivery_boy/features/wallet/domain/repositories/wallet_repository.dart';
import 'package:sixvalley_delivery_boy/utill/app_constants.dart';

Future<Map<String, Map<String, String>>> init() async {
  // Core
  final sharedPreferences = await SharedPreferences.getInstance();
  Get.lazyPut(() => sharedPreferences);
  Get.lazyPut(() => ApiClient(appBaseUrl: AppConstants.baseUri, sharedPreferences: Get.find()));


  ///Interface
  AuthRepositoryInterface authRepoInterface = AuthRepository(apiClient: Get.find(), sharedPreferences: Get.find());
  Get.lazyPut(() => authRepoInterface);
  ChatRepositoryInterface chatRepoInterface = ChatRepository(apiClient: Get.find(), sharedPreferences: Get.find());
  Get.lazyPut(()=> chatRepoInterface);
  NotificationRepositoryInterface notificationRepoInterface = NotificationRepository(apiClient: Get.find(), sharedPreferences: Get.find());
  Get.lazyPut(()=> notificationRepoInterface);
  OnboardRepositoryInterface onboardRepoInterface = OnBoardingRepository();
  Get.lazyPut(()=> onboardRepoInterface);
  OrderRepositoryInterface orderRepoInterface = OrderRepository(apiClient: Get.find(), sharedPreferences: Get.find());
  Get.lazyPut(()=> orderRepoInterface);
  ProfileRepositoryInterface profileRepoInterface = ProfileRepository(apiClient: Get.find(), sharedPreferences: Get.find());
  Get.lazyPut(()=> profileRepoInterface);
  SplashRepositoryInterface splashRepoInterface = SplashRepository(sharedPreferences: Get.find(), apiClient: Get.find());
  Get.lazyPut(()=> splashRepoInterface);
  WalletRepositoryInterface walletRepoInterface = WalletRepository(apiClient: Get.find());
  Get.lazyPut(()=> walletRepoInterface);
  ReviewRepositoryInterface reviewRepoInterface = ReviewRepository(apiClient: Get.find(), sharedPreferences: Get.find());
  Get.lazyPut(()=> reviewRepoInterface);
  WithdrawRepositoryInterface withdrawRepoInterface = WithdrawRepository(apiClient: Get.find());
  Get.lazyPut(()=> withdrawRepoInterface);
  EmergencyContactRepositoryInterface emergencyContactRepoInterface = EmergencyContactRepository(apiClient: Get.find());
  Get.lazyPut(()=> emergencyContactRepoInterface);
  OrderDetailsRepositoryInterface orderDetailsRepositoryInterface = OrderDetailsRepository(apiClient: Get.find());
  Get.lazyPut(()=> orderDetailsRepositoryInterface);


  AuthServiceInterface authServiceInterface = AuthService(authRepoInterface: Get.find());
  Get.lazyPut(() => authServiceInterface);
  ChatServiceInterface chatServiceInterface = ChatService(chatRepoInterface: Get.find());
  Get.lazyPut(()=> chatServiceInterface);
  NotificationServiceInterface notificationServiceInterface = NotificationService(notificationRepoInterfcace: Get.find());
  Get.lazyPut(()=> notificationServiceInterface);
  OnboardServiceInterface onboardServiceInterface = OnboardService(onboardRepoInterface: Get.find());
  Get.lazyPut(()=> onboardServiceInterface);
  OrderServiceInterface orderServiceInterface = OrderService(orderRepoInterface: Get.find());
  Get.lazyPut(()=> orderServiceInterface);
  ProfileServiceInterface profileServiceInterface = ProfileService(profileRepoInterface: Get.find());
  Get.lazyPut(()=> profileServiceInterface);
  SplashServiceInterface splashServiceInterface = SplashService(splashRepoInterface: Get.find());
  Get.lazyPut(()=> splashServiceInterface);
  WalletServiceInterface walletServiceInterface = WalletService(walletRepoInterface: Get.find());
  Get.lazyPut(()=> walletServiceInterface);
  ReviewServiceInterface reviewServiceInterface = ReviewService(reviewRepoInterface: Get.find());
  Get.lazyPut(()=> reviewServiceInterface);
  WithdrawServiceInterface withdrawServiceInterface = WithdrawService(withdrawRepoInterface: Get.find());
  Get.lazyPut(()=> withdrawServiceInterface);
  EmergencyContactServiceInterface emergencyContactServiceInterface = EmergencyContactService(emergencyContactRepoInterface: Get.find());
  Get.lazyPut(()=> emergencyContactServiceInterface);
  OrderDetailsServiceInterface orderDetailsServiceInterface = OrderDetailsService(orderDetailsRepositoryInterface: Get.find());
  Get.lazyPut(()=> orderDetailsServiceInterface);

  ///service
  Get.lazyPut(() => AuthService(authRepoInterface: Get.find()));
  Get.lazyPut(() => ChatService(chatRepoInterface: Get.find()));
  Get.lazyPut(() => NotificationService(notificationRepoInterfcace: Get.find()));
  Get.lazyPut(()=> OnboardService(onboardRepoInterface: Get.find()));
  Get.lazyPut(()=> OrderService(orderRepoInterface: Get.find()));
  Get.lazyPut(()=> ProfileService(profileRepoInterface: Get.find()));
  Get.lazyPut(()=> SplashService(splashRepoInterface: Get.find()));
  Get.lazyPut(()=> WalletService(walletRepoInterface: Get.find()));
  Get.lazyPut(()=> ReviewService(reviewRepoInterface: Get.find()));
  Get.lazyPut(()=> WithdrawService(withdrawRepoInterface: Get.find()));
  Get.lazyPut(()=> EmergencyContactService(emergencyContactRepoInterface: Get.find()));
  Get.lazyPut(()=> OrderDetailsService(orderDetailsRepositoryInterface: Get.find()));



  /// Repository
  Get.lazyPut(() => SplashRepository(sharedPreferences: Get.find(), apiClient: Get.find()));
  Get.lazyPut(() => OnBoardingRepository());
  Get.lazyPut(() => LanguageRepository());
  Get.lazyPut(() => ProfileRepository(apiClient: Get.find(), sharedPreferences: sharedPreferences));
  Get.lazyPut(() => AuthRepository(apiClient: Get.find(), sharedPreferences: Get.find()));
  Get.lazyPut(() => OrderRepository(apiClient: Get.find(), sharedPreferences: Get.find()));
  Get.lazyPut(() => ChatRepository(apiClient: Get.find(), sharedPreferences:  Get.find()));
  Get.lazyPut(() => NotificationRepository(apiClient: Get.find(), sharedPreferences:  Get.find()));
  Get.lazyPut(() => WalletRepository(apiClient: Get.find()));
  Get.lazyPut(() => RiderRepository(apiClient: Get.find()));
  Get.lazyPut(() => ReviewRepository(apiClient: Get.find(), sharedPreferences: Get.find()));
  Get.lazyPut(()=> WithdrawRepository(apiClient: Get.find()));
  Get.lazyPut(()=> EmergencyContactRepository(apiClient: Get.find()));
  Get.lazyPut(()=> OrderDetailsRepository(apiClient: Get.find()));


  /// Controller

  Get.lazyPut(() => AuthController(authServiceInterface: AuthService(authRepoInterface: Get.find())));
  Get.lazyPut(() => ChatController(chatServiceInterFace: Get.find()));
  Get.lazyPut(() => NotificationController(notificationServiceInterface: Get.find()));
  Get.lazyPut(() => OnBoardingController(onboardServiceInterface: Get.find()));
  Get.lazyPut(() => OrderController(orderServiceInterface: Get.find()));
  Get.lazyPut(() => ProfileController(profileServiceInterface: Get.find()));
  Get.lazyPut(() => SplashController(splashServiceInterface: Get.find()));
  Get.lazyPut(() => WalletController(walletServiceInterface: Get.find()));
  Get.lazyPut(()=> ReviewController(reviewServiceInterface: Get.find()));
  Get.lazyPut(()=> WithdrawController(withdrawServiceInterface: Get.find()));
  Get.lazyPut(()=> EmergencyContactController(emergencyContactServiceInterface: Get.find()));
  Get.lazyPut(()=> OrderDetailsController(orderDetailsServiceInterface: Get.find()));

  Get.lazyPut(() => LocalizationController(sharedPreferences: sharedPreferences));
  Get.lazyPut(() => RiderController(riderRepo : Get.find()));
  Get.lazyPut(() => DashboardController());
  Get.lazyPut(() => ThemeController(sharedPreferences: Get.find()));
  Get.lazyPut(() => LocalizationController(sharedPreferences: Get.find()));
  Get.lazyPut(() => LanguageController(sharedPreferences: Get.find()));


  /// Retrieving localized data
  Map<String, Map<String, String>> _languages = {};
  for(LanguageModel languageModel in AppConstants.languages) {
    String jsonStringValues =  await rootBundle.loadString('assets/language/${languageModel.languageCode}.json');
    Map<String, dynamic> _mappedJson = json.decode(jsonStringValues);
    Map<String, String> _json = {};
    _mappedJson.forEach((key, value) {
      _json[key] = value.toString();
    });
    _languages['${languageModel.languageCode}_${languageModel.countryCode}'] = _json;
  }
  return _languages;
}

