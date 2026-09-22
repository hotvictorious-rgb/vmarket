import 'package:flutter_sixvalley_ecommerce/features/cashback/controllers/cashback_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/cashback/domain/repositories/cashback_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/cashback/domain/repositories/cashback_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/cashback/domain/services/cashback_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/cashback/domain/services/cashback_service_interface.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_sixvalley_ecommerce/data/datasource/remote/dio/dio_client.dart';
import 'package:flutter_sixvalley_ecommerce/data/reposotories/data_sync_repo.dart';
import 'package:flutter_sixvalley_ecommerce/data/reposotories/data_sync_repo_interface.dart';
import 'package:flutter_sixvalley_ecommerce/data/services/data_sync_service.dart';
import 'package:flutter_sixvalley_ecommerce/data/services/data_sync_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/address/domain/repositories/address_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/address/domain/services/address_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/address/domain/services/address_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/controllers/facebook_login_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/controllers/google_login_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/domain/repositories/auth_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/domain/repositories/auth_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/domain/services/auth_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/domain/services/auth_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/banner/controllers/banner_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/banner/domain/repositories/banner_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/banner/domain/repositories/banner_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/banner/domain/services/banner_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/banner/domain/services/banner_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/brand/domain/repositories/brand_repo_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/brand/domain/repositories/brand_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/brand/domain/services/brand_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/brand/domain/services/brand_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/domain/repositories/cart_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/domain/repositories/cart_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/domain/services/cart_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/domain/services/cart_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/category/domain/repositories/category_repo.dart';
import 'package:flutter_sixvalley_ecommerce/features/category/domain/repositories/category_repo_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/category/domain/services/category_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/category/domain/services/category_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/controllers/checkout_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/repositories/checkout_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/repositories/checkout_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/services/checkout_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/services/checkout_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/fulfillment/controllers/fulfillment_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/fulfillment/domain/repositories/fulfillment_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/fulfillment/domain/repositories/fulfillment_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/fulfillment/domain/services/fulfillment_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/fulfillment/domain/services/fulfillment_service_interface.dart';

import 'package:flutter_sixvalley_ecommerce/features/contact_us/controllers/contact_us_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/contact_us/domain/repository/contact_us_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/contact_us/domain/repository/contact_us_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/contact_us/domain/services/contact_us_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/contact_us/domain/services/contact_us_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/address/domain/repositories/address_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/location/controllers/location_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/location/domain/repositories/location_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/location/domain/repositories/location_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/location/domain/services/location_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/location/domain/services/location_service_interface.dart';

import 'package:flutter_sixvalley_ecommerce/features/notification/controllers/notification_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/notification/domain/repositories/notification_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/notification/domain/repositories/notification_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/notification/domain/services/notification_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/notification/domain/services/notification_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/onboarding/controllers/onboarding_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/onboarding/domain/repositories/onboarding_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/onboarding/domain/repositories/onboarding_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/onboarding/domain/services/onboarding_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/onboarding/domain/services/onboarding_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/order/controllers/order_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/order/domain/repositories/order_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/order/domain/repositories/order_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/order/domain/services/order_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/order/domain/services/order_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/controllers/order_details_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/domain/repositories/order_details_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/domain/repositories/order_details_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/domain/services/order_details_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/domain/services/order_details_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/controllers/product_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/controllers/seller_product_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/domain/repositories/product_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/domain/repositories/product_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/domain/repositories/seller_product_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/domain/repositories/seller_product_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/domain/services/product_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/domain/services/product_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/domain/services/seller_product_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/domain/services/seller_product_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/product_details/controllers/product_details_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/product_details/domain/repositories/product_details_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/product_details/domain/repositories/product_details_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/product_details/domain/services/product_details_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/product_details/domain/services/product_details_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/profile/controllers/profile_contrroller.dart';
import 'package:flutter_sixvalley_ecommerce/features/profile/domain/repositories/profile_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/profile/domain/repositories/profile_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/profile/domain/services/profile_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/profile/domain/services/profile_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/refund/controllers/refund_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/refund/domain/repositories/refund_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/refund/domain/repositories/refund_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/refund/domain/services/refund_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/refund/domain/services/refund_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/reorder/controllers/re_order_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/reorder/domain/repositories/re_order_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/reorder/domain/repositories/re_order_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/reorder/domain/services/re_order_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/reorder/domain/services/re_order_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/review/controllers/review_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/review/domain/repositories/review_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/review/domain/repositories/review_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/review/domain/services/review_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/review/domain/services/review_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/search_product/domain/repositories/search_product_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/search_product/domain/services/search_product_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/search_product/domain/services/search_product_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/shipping/controllers/shipping_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/shipping/domain/repositories/shipping_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/shipping/domain/repositories/shipping_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/shipping/domain/services/shipping_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/shipping/domain/services/shipping_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/shop/domain/repositories/shop_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/shop/domain/repositories/shop_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/shop/domain/services/shop_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/shop/domain/services/shop_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/controllers/splash_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/domain/repositories/splash_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/domain/repositories/splash_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/domain/services/splash_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/domain/services/splash_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/support/controllers/support_ticket_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/support/domain/repositories/support_ticket_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/support/domain/repositories/support_ticket_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/support/domain/services/support_ticket_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/support/domain/services/support_ticket_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/wishlist/controllers/wishlist_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/wishlist/domain/repositories/wishlist_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/wishlist/domain/repositories/wishlist_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/wishlist/domain/services/wishlist_service.dart';
import 'package:flutter_sixvalley_ecommerce/features/wishlist/domain/services/wishlist_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/helper/network_info.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/controllers/auth_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/brand/controllers/brand_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/controllers/cart_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/category/controllers/category_controller.dart';
import 'package:flutter_sixvalley_ecommerce/localization/controllers/localization_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/address/controllers/address_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/search_product/controllers/search_product_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/shop/controllers/shop_controller.dart';
import 'package:flutter_sixvalley_ecommerce/theme/controllers/theme_controller.dart';
import 'package:flutter_sixvalley_ecommerce/utill/app_constants.dart';
import 'package:get_it/get_it.dart';
import 'package:flutter_sixvalley_ecommerce/services/storage_service.dart';
import 'data/datasource/remote/dio/logging_interceptor.dart';

import 'features/search_product/domain/repositories/search_product_repository.dart';

final sl = GetIt.instance;

Future<void> init() async {
  // Core
  const secureStorage = FlutterSecureStorage();
  sl.registerLazySingleton(() => secureStorage);
  final storageService = StorageService(secureStorage: secureStorage);
  await storageService.init();
  sl.registerLazySingleton(() => storageService);

  // [AI] Pre-load secure token synchronously from unified storage cache on startup
  String? secureToken = storageService.getString(AppConstants.userLoginToken);

  sl.registerLazySingleton(() => Dio());
  sl.registerLazySingleton(() => LoggingInterceptor());
  sl.registerLazySingleton(() => Connectivity());
  sl.registerLazySingleton(() => NetworkInfo(sl()));
  sl.registerLazySingleton(() => DioClient(AppConstants.baseUrl, sl(), loggingInterceptor: sl(), storageService: sl(), token: secureToken));


  DataSyncRepoInterface dataSyncRepoInterface = DataSyncRepo(dioClient: sl(), storageService: sl());
  sl.registerLazySingleton(() => dataSyncRepoInterface);
  DataSyncServiceInterface dataSyncServiceInterface = DataSyncService(dataSyncRepoInterface: sl());
  sl.registerLazySingleton(() => dataSyncServiceInterface);


  // Repository
  sl.registerLazySingleton(() => DataSyncRepo(dioClient: sl(), storageService: sl()));
  sl.registerLazySingleton(() => CategoryRepository(dioClient: sl(), dataSyncRepoInterface: sl()));
  sl.registerLazySingleton(() => BrandRepository(dioClient: sl(), dataSyncRepoInterface: sl()));
  sl.registerLazySingleton(() => ProductRepository(dioClient: sl(), dataSyncRepoInterface: sl()));
  sl.registerLazySingleton(() => BannerRepository(dioClient: sl(), dataSyncRepoInterface: sl()));
  sl.registerLazySingleton(() => OnBoardingRepository(dioClient: sl()));
  sl.registerLazySingleton(() => AuthRepository(dioClient: sl(), storageService: sl()));
  sl.registerLazySingleton(() => ProductDetailsRepository(dioClient: sl()));
  sl.registerLazySingleton(() => SearchProductRepository(dioClient: sl(), storageService: sl()));
  sl.registerLazySingleton(() => OrderRepository(dioClient: sl()));
  sl.registerLazySingleton(() => ShopRepository(dioClient: sl(), dataSyncRepoInterface: sl()));
  sl.registerLazySingleton(() => NotificationRepository(dioClient: sl()));
  sl.registerLazySingleton(() => ProfileRepository(dioClient: sl()));
  sl.registerLazySingleton(() => WishListRepository(dioClient: sl()));
  sl.registerLazySingleton(() => CartRepository(dioClient: sl(), dataSyncRepoInterface: sl()));
  sl.registerLazySingleton(() => SplashRepository(storageService: sl(), dioClient: sl()));
  sl.registerLazySingleton(() => SupportTicketRepository(dioClient: sl()));
  sl.registerLazySingleton(() => AddressRepository(dioClient: sl()));

  sl.registerLazySingleton(() => CheckoutRepository(dioClient: sl()));
  sl.registerLazySingleton(() => LocationRepository(dioClient: sl()));
  sl.registerLazySingleton(() => ShippingRepository(dioClient: sl()));
  sl.registerLazySingleton(() => ContactUsRepository(dioClient: sl()));
  sl.registerLazySingleton(() => SellerProductRepository(dioClient: sl(), dataSyncRepoInterface: sl()));
  sl.registerLazySingleton(() => OrderDetailsRepository(dioClient: sl()));
  sl.registerLazySingleton(() => RefundRepository(dioClient: sl()));
  sl.registerLazySingleton(() => ReOrderRepository(dioClient: sl()));


  // Provider
  sl.registerFactory(() => CategoryController(categoryServiceInterface: sl()));
  sl.registerFactory(() => ShopController(shopServiceInterface: sl()));
  sl.registerFactory(() => BrandController(brandRepo: sl()));
  sl.registerFactory(() => ProductController(productServiceInterface: sl()));
  sl.registerFactory(() => BannerController(bannerServiceInterface: sl()));
  sl.registerFactory(() => OnBoardingController(onBoardingServiceInterface: sl()));
  sl.registerFactory(() => AuthController(authServiceInterface: sl()));
  sl.registerFactory(() => ProductDetailsController(productDetailsServiceInterface: sl()));
  sl.registerFactory(() => SearchProductController(searchProductServiceInterface: sl()));
  sl.registerFactory(() => OrderController(orderServiceInterface: sl()));
  sl.registerFactory(() => NotificationController(notificationServiceInterface: sl()));
  sl.registerFactory(() => ProfileController(profileServiceInterface: sl()));
  sl.registerFactory(() => WishListController(wishlistServiceInterface: sl()));
  sl.registerFactory(() => SplashController(splashServiceInterface: sl()));
  sl.registerFactory(() => CartController(cartServiceInterface: sl()));
  sl.registerFactory(() => SupportTicketController(supportTicketServiceInterface: sl()));
  sl.registerFactory(() => LocalizationController(storageService: sl(), dioClient: sl()));
  sl.registerFactory(() => ThemeController(storageService: sl()));
  sl.registerFactory(() => GoogleSignInController());
  sl.registerFactory(() => FacebookLoginController());
  sl.registerFactory(() => AddressController(addressServiceInterface: sl()));
  // [AI] Cashback Feature Registration
  sl.registerLazySingleton<CashbackRepositoryInterface>(() => CashbackRepository(dioClient: sl()));
  sl.registerLazySingleton<CashbackServiceInterface>(() => CashbackService(cashbackRepositoryInterface: sl()));
  sl.registerFactory(() => CashbackController(cashbackServiceInterface: sl()));

  // [AI] Fulfillment Domain (Phase G)
  sl.registerLazySingleton<FulfillmentRepositoryInterface>(() => FulfillmentRepository(dioClient: sl()));
  sl.registerLazySingleton<FulfillmentServiceInterface>(() => FulfillmentService(fulfillmentRepositoryInterface: sl()));
  sl.registerFactory(() => FulfillmentController(fulfillmentServiceInterface: sl()));

  sl.registerFactory(() => CheckoutController(checkoutServiceInterface: sl()));
  sl.registerFactory(() => LocationController(locationServiceInterface: sl()));
  // [LEGACY MIGRATION] ShippingController pending removal in Phase M
  sl.registerFactory(() => ShippingController(shippingServiceInterface: sl()));
  sl.registerFactory(() => ContactUsController(contactUsServiceInterface: sl()));
  sl.registerFactory(() => ReviewController(reviewServiceInterface: sl()));
  sl.registerFactory(() => SellerProductController(sellerProductServiceInterface: sl()));
  sl.registerFactory(() => OrderDetailsController(orderDetailsServiceInterface: sl()));
  sl.registerFactory(() => RefundController(refundServiceInterface: sl()));
  sl.registerFactory(() => ReOrderController(reOrderServiceInterface: sl()));

  //interface
  AddressRepoInterface addressRepoInterface = AddressRepository(dioClient: sl());
  sl.registerLazySingleton(() => addressRepoInterface);
  AddressServiceInterface addressServiceInterface = AddressService(addressRepoInterface: sl());
  sl.registerLazySingleton(() => addressServiceInterface);

  AuthRepoInterface authRepoInterface = AuthRepository(dioClient: sl(), storageService: sl());
  sl.registerLazySingleton(() => authRepoInterface);
  AuthServiceInterface authServiceInterface = AuthService(authRepoInterface: sl());
  sl.registerLazySingleton(() => authServiceInterface);


  BannerRepositoryInterface bannerRepositoryInterface = BannerRepository(dioClient: sl(), dataSyncRepoInterface: sl());
  sl.registerLazySingleton(() => bannerRepositoryInterface);
  BannerServiceInterface bannerServiceInterface = BannerService(bannerRepositoryInterface: sl());
  sl.registerLazySingleton(() => bannerServiceInterface);

  BrandRepoInterface brandRepoInterface = BrandRepository(dioClient: sl(), dataSyncRepoInterface: sl());
  sl.registerLazySingleton(() => brandRepoInterface);
  BrandServiceInterface brandServiceInterface = BrandService(brandRepoInterface: sl());
  sl.registerLazySingleton(() => brandServiceInterface);

  CartRepositoryInterface cartRepositoryInterface = CartRepository(dioClient: sl(), dataSyncRepoInterface: sl());
  sl.registerLazySingleton(() => cartRepositoryInterface);
  CartServiceInterface cartServiceInterface = CartService(cartRepositoryInterface: sl());
  sl.registerLazySingleton(() => cartServiceInterface);

  CategoryRepoInterface categoryRepoInterface = CategoryRepository(dioClient: sl(), dataSyncRepoInterface: sl());
  sl.registerLazySingleton(() => categoryRepoInterface);
  CategoryServiceInterface categoryServiceInterface = CategoryService(categoryRepoInterface: sl());
  sl.registerLazySingleton(() => categoryServiceInterface);

  ShippingRepositoryInterface shippingRepositoryInterface = ShippingRepository(dioClient: sl());
  sl.registerLazySingleton(() => shippingRepositoryInterface);
  ShippingServiceInterface shippingServiceInterface = ShippingService(shippingRepositoryInterface: sl());
  sl.registerLazySingleton(() => shippingServiceInterface);


  CheckoutRepositoryInterface checkoutRepositoryInterface = CheckoutRepository(dioClient: sl());
  sl.registerLazySingleton(() => checkoutRepositoryInterface);
  CheckoutServiceInterface checkoutServiceInterface = CheckoutService(checkoutRepositoryInterface: sl());
  sl.registerLazySingleton(() => checkoutServiceInterface);



  ContactUsRepositoryInterface contactUsRepositoryInterface = ContactUsRepository(dioClient: sl());
  sl.registerLazySingleton(() => contactUsRepositoryInterface);
  ContactUsServiceInterface contactUsServiceInterface = ContactUsService(contactUsRepositoryInterface: sl());
  sl.registerLazySingleton(() => contactUsServiceInterface);

  LocationRepositoryInterface locationRepositoryInterface = LocationRepository(dioClient: sl());
  sl.registerLazySingleton(() => locationRepositoryInterface);
  LocationServiceInterface locationServiceInterface = LocationService(locationRepoInterface: sl());
  sl.registerLazySingleton(() => locationServiceInterface);



  NotificationRepositoryInterface notificationRepositoryInterface = NotificationRepository(dioClient: sl());
  sl.registerLazySingleton(() => notificationRepositoryInterface);
  NotificationServiceInterface notificationServiceInterface = NotificationService(notificationRepositoryInterface: sl());
  sl.registerLazySingleton(() => notificationServiceInterface);

  OnBoardingRepositoryInterface onBoardingRepositoryInterface = OnBoardingRepository(dioClient: sl());
  sl.registerLazySingleton(() => onBoardingRepositoryInterface);
  OnBoardingServiceInterface onBoardingServiceInterface = OnBoardingService(onBoardingRepositoryInterface: sl());
  sl.registerLazySingleton(() => onBoardingServiceInterface);

  OrderRepositoryInterface orderRepositoryInterface = OrderRepository(dioClient: sl());
  sl.registerLazySingleton(() => orderRepositoryInterface);
  OrderServiceInterface orderServiceInterface = OrderService(orderRepositoryInterface: sl());
  sl.registerLazySingleton(() => orderServiceInterface);

  OrderDetailsRepositoryInterface orderDetailsRepositoryInterface = OrderDetailsRepository(dioClient: sl());
  sl.registerLazySingleton(() => orderDetailsRepositoryInterface);
  OrderDetailsServiceInterface orderDetailsServiceInterface = OrderDetailsService(orderDetailsRepositoryInterface: sl());
  sl.registerLazySingleton(() => orderDetailsServiceInterface);


  RefundRepositoryInterface refundRepositoryInterface = RefundRepository(dioClient: sl());
  sl.registerLazySingleton(() => refundRepositoryInterface);
  RefundServiceInterface refundServiceInterface = RefundService(refundRepositoryInterface: sl());
  sl.registerLazySingleton(() => refundServiceInterface);


  ReOrderRepositoryInterface reOrderRepositoryInterface = ReOrderRepository(dioClient: sl());
  sl.registerLazySingleton(() => reOrderRepositoryInterface);
  ReOrderServiceInterface reOrderServiceInterface = ReOrderService(reOrderRepositoryInterface: sl());
  sl.registerLazySingleton(() => reOrderServiceInterface);

  ReviewRepositoryInterface reviewRepositoryInterface = ReviewRepository(dioClient: sl());
  sl.registerLazySingleton(() => reviewRepositoryInterface);
  ReviewServiceInterface reviewServiceInterface = ReviewService(reviewRepositoryInterface: sl());
  sl.registerLazySingleton(() => reviewServiceInterface);

  ProductDetailsRepositoryInterface productDetailsRepositoryInterface = ProductDetailsRepository(dioClient: sl());
  sl.registerLazySingleton(() => productDetailsRepositoryInterface);
  ProductDetailsServiceInterface productDetailsServiceInterface = ProductDetailsService(productDetailsRepositoryInterface: sl());
  sl.registerLazySingleton(() => productDetailsServiceInterface);

  SellerProductRepositoryInterface sellerProductRepositoryInterface = SellerProductRepository(dioClient: sl(), dataSyncRepoInterface: sl());
  sl.registerLazySingleton(() => sellerProductRepositoryInterface);
  SellerProductServiceInterface sellerProductServiceInterface = SellerProductService(sellerProductRepositoryInterface: sl());
  sl.registerLazySingleton(() => sellerProductServiceInterface);

  ShopRepositoryInterface shopRepositoryInterface = ShopRepository(dioClient: sl(), dataSyncRepoInterface: sl());
  sl.registerLazySingleton(() => shopRepositoryInterface);
  ShopServiceInterface shopServiceInterface = ShopService(shopRepositoryInterface: sl());
  sl.registerLazySingleton(() => shopServiceInterface);

  ProductRepositoryInterface productRepositoryInterface = ProductRepository(dioClient: sl(), dataSyncRepoInterface: sl());
  sl.registerLazySingleton(() => productRepositoryInterface);
  ProductServiceInterface productServiceInterface = ProductService(productRepositoryInterface: sl());
  sl.registerLazySingleton(() => productServiceInterface);

  ProfileRepositoryInterface profileRepositoryInterface = ProfileRepository(dioClient: sl());
  sl.registerLazySingleton(() => profileRepositoryInterface);
  ProfileServiceInterface profileServiceInterface = ProfileService(profileRepositoryInterface: sl());
  sl.registerLazySingleton(() => profileServiceInterface);

  SplashRepositoryInterface splashRepositoryInterface = SplashRepository(dioClient: sl(), storageService: sl());
  sl.registerLazySingleton(() => splashRepositoryInterface);
  SplashServiceInterface splashServiceInterface = SplashService(splashRepositoryInterface: sl());
  sl.registerLazySingleton(() => splashServiceInterface);

  SupportTicketRepositoryInterface supportTicketRepositoryInterface = SupportTicketRepository(dioClient: sl());
  sl.registerLazySingleton(() => supportTicketRepositoryInterface);
  SupportTicketServiceInterface supportTicketServiceInterface = SupportTicketService(supportTicketRepositoryInterface: sl());
  sl.registerLazySingleton(() => supportTicketServiceInterface);

  WishListRepositoryInterface wishListRepositoryInterface = WishListRepository(dioClient: sl());
  sl.registerLazySingleton(() => wishListRepositoryInterface);
  WishlistServiceInterface wishlistServiceInterface = WishListService(wishListRepositoryInterface: sl());
  sl.registerLazySingleton(() => wishlistServiceInterface);


  SearchProductRepositoryInterface searchProductRepositoryInterface = SearchProductRepository(dioClient: sl(), storageService: sl());
  sl.registerLazySingleton(() => searchProductRepositoryInterface);
  SearchProductServiceInterface searchProductServiceInterface = SearchProductService(searchProductRepositoryInterface: sl());
  sl.registerLazySingleton(() => searchProductServiceInterface);

  // DataSyncRepoInterface dataSyncRepoInterface = DataSyncRepo(dioClient: sl(), storageService: sl());
  // sl.registerLazySingleton(() => dataSyncRepoInterface);
  // DataSyncServiceInterface dataSyncServiceInterface = DataSyncService(dataSyncRepoInterface: sl());
  // sl.registerLazySingleton(() => dataSyncServiceInterface);




  //services
  sl.registerLazySingleton(() => AddressService(addressRepoInterface : sl()));
  sl.registerLazySingleton(() => AuthService(authRepoInterface : sl()));
  sl.registerLazySingleton(() => BannerService(bannerRepositoryInterface : sl()));
  sl.registerLazySingleton(() => BrandService(brandRepoInterface : sl()));
  sl.registerLazySingleton(() => CartService(cartRepositoryInterface : sl()));
  sl.registerLazySingleton(() => CategoryService(categoryRepoInterface : sl()));
  sl.registerLazySingleton(() => ShippingService(shippingRepositoryInterface : sl()));
  sl.registerLazySingleton(() => CheckoutService(checkoutRepositoryInterface : sl()));

  sl.registerLazySingleton(() => ContactUsService(contactUsRepositoryInterface : sl()));
  sl.registerLazySingleton(() => LocationService(locationRepoInterface : sl()));

  sl.registerLazySingleton(() => NotificationService(notificationRepositoryInterface : sl()));
  sl.registerLazySingleton(() => OnBoardingService(onBoardingRepositoryInterface : sl()));
  sl.registerLazySingleton(() => OrderService(orderRepositoryInterface : sl()));
  sl.registerLazySingleton(() => OrderDetailsService(orderDetailsRepositoryInterface : sl()));
  sl.registerLazySingleton(() => RefundService(refundRepositoryInterface : sl()));
  sl.registerLazySingleton(() => ReOrderService(reOrderRepositoryInterface : sl()));
  sl.registerLazySingleton(() => ReviewService(reviewRepositoryInterface : sl()));
  sl.registerLazySingleton(() => ProductDetailsService(productDetailsRepositoryInterface : sl()));
  sl.registerLazySingleton(() => SellerProductService(sellerProductRepositoryInterface : sl()));
  sl.registerLazySingleton(() => ShopService(shopRepositoryInterface : sl()));
  sl.registerLazySingleton(() => ProductService(productRepositoryInterface : sl()));
  sl.registerLazySingleton(() => ProfileService(profileRepositoryInterface : sl()));
  sl.registerLazySingleton(() => SplashService(splashRepositoryInterface : sl()));
  sl.registerLazySingleton(() => SupportTicketService(supportTicketRepositoryInterface : sl()));
  sl.registerLazySingleton(() => WishListService(wishListRepositoryInterface : sl()));
  sl.registerLazySingleton(() => SearchProductService(searchProductRepositoryInterface : sl()));
}
