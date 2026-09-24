import 'package:sixvalley_vendor_app/localization/models/language_model.dart';
import 'package:sixvalley_vendor_app/utill/images.dart';
import '../features/shop/domain/models/guideline_model.dart';

class AppConstants {
  static const String appName = 'Victorious Vendor'; ///Flutter SDK 3.41.1
  static const String appBadge = 'MERCHANT COMMAND CENTER';
  static const String slogan = 'Your Trusted Online Market For Quality Products';
  static const String appVersion = '16.1';
  static const String companyName = 'Victorious MARKET';
  static const bool demo = false;
  static const int imageQuality = 100;

  static const String baseUrl = 'https://shop.victoriousmarket.com.ng';

  static const String loginUri = '/api/v3/seller/auth/login';
  static const String configUri = '/api/v1/config';
  static const String sellerUri = '/api/v3/seller/seller-info';
  static const String sellerAndBankUpdate = '/api/v3/seller/seller-update';
  static const String getNigerianBanksUri = '/api/v3/seller/paystack/banks';
  static const String resolveAccountUri = '/api/v3/seller/paystack/resolve-account';
  static const String sendBankOtpUri = '/api/v3/seller/bank-info/send-otp';
  static const String getKycStatusUri = '/api/v3/seller/kyc/status';
  static const String submitKycUri = '/api/v3/seller/kyc/submit';
  static const String shopUri = '/api/v3/seller/shop-info';

  static const String shopUpdate = '/api/v3/seller/shop-update';
  static const String orderListUri = '/api/v3/seller/orders/list';
  static const String orderDetails = '/api/v3/seller/orders/';
  static const String orderInvoice = '/api/v3/seller/orders/invoice/';
  static const String updateOrderStatus = '/api/v3/seller/orders/order-detail-status/';
  static const String balanceWithdraw = '/api/v3/seller/balance-withdraw';
  static const String cancelBalanceRequest = '/api/v3/seller/close-withdraw-request';
  static const String transactionUri = '/api/v3/seller/transactions?status=';
  static const String sellerProductUri = '/api/v3/seller/products/';
  static const String posProductList = '/api/v3/seller/products/search';
  static const String searchPosProductList = '/api/v3/seller/products/search';
  static const String stockOutProductUri = '/api/v3/seller/products/stock-out-list?limit=10&offset=';
  static const String productReviewUri = '/api/v3/seller/shop-product-reviews';
  static const String productReviewStatusOnOff = '/api/v3/seller/shop-product-reviews-status';
  static const String attributeUri = '/api/v1/attributes';
  static const String brandUri = '/api/v3/seller/brands';
  static const String categoryUri = '/api/v3/seller/categories';
  static const String subCategoryUri = '/api/v1/categories/childes/';
  static const String subSubCategoryUri = '/api/v1/categories/childes/childes/';
  static const String addProductUri = '/api/v3/seller/products/add';
  static const String uploadProductImageUri = '/api/v3/seller/products/upload-images';
  static const String updateProductUri = '/api/v3/seller/products/update';
  static const String deleteProductUri = '/api/v3/seller/products/delete';
  static const String editProductUri = '/api/v3/seller/products/edit';
  static const String confirmMarketplaceAvailabilityUri = '/api/v3/seller/products/confirm-availability';
  static const String confirmAndRelistMarketplaceUri = '/api/v3/seller/products/confirm-and-relist';
  static const String updateMarketplaceAvailabilityUri = '/api/v3/seller/products/update-marketplace-availability';
  static const String updateMarketplaceListingUri = '/api/v3/seller/products/update-marketplace-listing';
  static const String bulkConfirmMarketplaceAvailabilityUri = '/api/v3/seller/products/bulk-confirm-availability';
  static const String getEmployeeListUri = '/api/v3/seller/employee/list';
  static const String addEmployeeUri = '/api/v3/seller/employee/store';
  static const String updateEmployeeStatusUri = '/api/v3/seller/employee/status';
  static const String deleteEmployeeUri = '/api/v3/seller/employee/delete';
  static const String tokenUri = '/api/v3/seller/cm-firebase-token';
  static const String refundListUri = '/api/v3/seller/refund/list';
  static const String refundItemDetails = '/api/v3/seller/refund/refund-details';
  static const String refundReqStatusUpdate = '/api/v3/seller/refund/refund-status-update';
  static const String forgotPasswordUri = '/api/v3/seller/auth/forgot-password';
  static const String verifyOtpUri = '/api/v3/seller/auth/verify-otp';
  static const String resetPasswordUri = '/api/v3/seller/auth/reset-password';
  static const String registration = '/api/v3/seller/registration';
  static const String deleteAccount = '/api/v3/seller/account-delete';

  static const String topSellingProduct = '/api/v3/seller/products/top-selling-product?limit=10&offset=';
  static const String mostPopularProduct = '/api/v3/seller/products/most-popular-product?limit=10&offset=';
  static const String topDeliveryMan = '/api/v3/seller/top-delivery-man';

  static const String updateProductQuantity = '/api/v3/seller/products/quantity-update';
  static const String productWiseReviewList = '/api/v3/seller/products/review-list/';
  static const String emergencyContactAdd = '/api/v3/seller/delivery-man/emergency-contact/store';
  static const String emergencyContactUpdate = '/api/v3/seller/delivery-man/emergency-contact/update';
  static const String getEmergencyContactList = '/api/v3/seller/delivery-man/emergency-contact/list';
  static const String emergencyContactStatusOnOff = '/api/v3/seller/delivery-man/emergency-contact/status-update';
  static const String emergencyContactDelete = '/api/v3/seller/delivery-man/emergency-contact/delete';

  static const String productStatusOnOff = '/api/v3/seller/products/status-update';
  static const String businessAnalytics = '/api/v3/seller/order-statistics?statistics_type=';
  static const String productDetails = '/api/v3/seller/products/details/';
  static const String chartFilterData = '/api/v3/seller/get-earning-statitics?type=';
  static const String temporaryClose = '/api/v3/seller/temporary-close';
  static const String vacation = '/api/v3/seller/vacation-add';
  static const String dynamicWithdrawMethod = '/api/v3/seller/withdraw-method-list';
  static const String getNotificationList = '/api/v3/seller/notification?limit=20&offset=';
  static const String seenNotification = '/api/v3/seller/notification/view?id=';
  static const String stockLimitStatus = '/api/v3/seller/products/stock-limit-status';
  static const String reviewReply = '/api/v3/seller/shop-product-reviews-reply';
  static const String getSingleRefundModel = '/api/v3/seller/refund/single-item?id=';
  static const String setUpOrder = '/api/v3/seller/orders/order-detail-info-update';
  static const String verifyPickupOtpUri = '/api/v3/seller/orders/verify-pickup-otp';
  static const String verifyPickupReservationUri = '/api/v3/seller/pickup-reservations/verify';
  static const String acceptPickupReservationUri = '/api/v3/seller/pickup-reservations/accept';
  static const String rejectPickupReservationUri = '/api/v3/seller/pickup-reservations/reject';
  static const String businessPagesUri = '/api/v1/business-pages?type=';
  static const String paymentWithdrawalMethodList = '/api/v3/seller/payment-information/withdrawal-method-list';
  static const String paymentInformationAdd = '/api/v3/seller/payment-information/add';
  static const String paymentInformationList = '/api/v3/seller/payment-information/list';
  static const String paymentInformationStatusUpdate = '/api/v3/seller/payment-information/status';
  static const String paymentInformationDelete = '/api/v3/seller/payment-information/delete';
  static const String paymentInformationDefault = '/api/v3/seller/payment-information/default';
  static const String paymentInformationUpdate = '/api/v3/seller/payment-information/update';
  static const String updateSetupGuideApp = '/api/v3/seller/update-setup-guide-app';
  static const String getTaxVatList = '/api/v1/vat-tax/get-taxVat-list';
  static const String firebaseAuthTokenStore = '/api/v3/seller/auth/firebase-auth-token-store';
  static const String firebaseAuthVerify = '/api/v3/seller/auth/firebase-auth-verify';
  static const String checkVendorExistInfoPhone = '/api/v3/seller/auth/check-vendor-exist-info';
  static const String generateInvoice = '/api/v1/customer/order/generate-invoice?order_id=';


  


  ///address
  static const String geocodeUri = '/api/v1/mapapi/geocode-api';
  static const String searchLocationUri = '/api/v1/mapapi/place-api-autocomplete';
  static const String placeDetailsUri = '/api/v1/mapapi/place-api-details';
  static const String setCurrentLanguageUri = '/api/v3/seller/language-change';

  static const String deleteProductImage = '/api/v3/seller/products/delete-images';
  static const String getProductImage = '/api/v3/seller/products/get-product-images/';
  static const String deleteProductPreview = '/api/v3/seller/products/delete-preview-file';


  static const String pending = 'pending';
  static const String confirmed = 'confirmed';
  static const String processing = 'processing';
  static const String processed = 'processed';
  static const String delivered = 'delivered';
  static const String failed = 'failed';
  static const String returned = 'returned';
  static const String cancelled = 'canceled';
  static const String outForDelivery = 'out_for_delivery';
  static const String approved = 'approved';
  static const String rejected = 'rejected';
  static const String done = 'refunded';

  static const String theme = 'theme';
  static const String currency = 'currency';
  static const String token = 'token';
  static const String countryCode = 'country_code';
  static const String languageCode = 'language_code';
  static const String cartList = 'cart_list';
  static const String userAddress = 'user_address';
  static const String userPassword = 'user_password';
  static const String userNumber = 'user_number';
  static const String searchAddress = 'search_address';
  static const String topic = 'six_valley_seller';
  static const String maintenanceModeTopic = 'maintenance_mode_start_vendor';
  static const String userEmail = 'user_email';
  static const String langKey = 'lang';
  static const String showCookies = 'cookies';


  static List<LanguageModel> languages = [
    LanguageModel(imageUrl: Images.unitedKingdom, languageName: 'English', countryCode: 'NG', languageCode: 'en'),
  ];

  static const double maxSizeOfASingleFile = 10;
  static const double maxLimitOfTotalFileSent = 5;
  static const double maxLimitOfFileSentINConversation = 25;
  static const int fileImageMaxLimit = 2;


  static const List<String> videoExtensions = [
    'mp4', 'mkv', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mpeg', 'mpg', 'm4v', '3gp', 'ogv'
  ];

  static const List<String> imageExtensions = [
    'png',
    'jpg',
    'jpeg',
    'gif',
    'webp',
  ];

  static const List<String> documentExtensions = [
    'doc', 'docx', 'txt', 'csv', 'xls', 'xlsx', 'pdf',
  ];

  static const List<String> disallowedExtensions = [
    'php','php3','php4','php5','php7','php8','phtml','phar',
    'asp','aspx','jsp','cgi','pl','py','rb',
    'js','mjs','html','htm','xhtml',
    'sh','bash','bat','cmd','ps1','zsh','ksh',
    'exe','dll','so','bin','msi','app',
    'java','class','jar',
    '7z','gz','bz2','xz',
    'env','ini','conf','config','yml','yaml','log',
    'sql','db','bak','old','swp','tmp'
  ];

  static const List<GuidelineModel> inHouseShopGuidelineList = [
    GuidelineModel('store_availability', 'store_availability_description'),
    GuidelineModel('shop_details', 'shop_details_description'),
    GuidelineModel('visit_website', 'visit_website_description'),
    GuidelineModel('edit_shop', 'edit_shop_description'),
  ];

  static const List<GuidelineModel> paymentInfoGuidelineList = [
    GuidelineModel('benefit', 'benefit_description'),
  ];

  static const List<GuidelineModel> otherSetupGuidelineList = [
    GuidelineModel('order_setup', 'order_setup_description'),
    GuidelineModel('business_tin', 'business_tin_description'),
  ];
  // static const double filterMaxPriceRange = 1000000;

}

extension StringExtension on String {
  String capitalize() {
    if (isEmpty) return this;
    return '${this[0].toUpperCase()}${substring(1)}';
  }
}
