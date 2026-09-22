import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/features/address/controllers/address_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/domain/models/cart_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/controllers/checkout_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/models/pickup_reservation_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/screens/pickup_reservation_success_screen.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/widgets/checkout_condition_checkbox.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/widgets/order_place_bottomsheet_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/widgets/payment_method_bottom_sheet_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/profile/controllers/profile_contrroller.dart';
import 'package:flutter_sixvalley_ecommerce/features/shipping/controllers/shipping_controller.dart';
import 'package:flutter_sixvalley_ecommerce/helper/cart_healper.dart';
import 'package:flutter_sixvalley_ecommerce/helper/debounce_helper.dart';
import 'package:flutter_sixvalley_ecommerce/helper/price_converter.dart';
import 'package:flutter_sixvalley_ecommerce/helper/route_healper.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/main.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/controllers/auth_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/controllers/cart_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/controllers/splash_controller.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/amount_widget.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/animated_custom_dialog_widget.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_app_bar_widget.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_button_widget.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/show_custom_snakbar_widget.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_textfield_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/widgets/choose_payment_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/widgets/shipping_details_widget.dart';
import 'package:provider/provider.dart';


class CheckoutScreen extends StatefulWidget {
  final List<CartModel> cartList;
  final bool fromProductDetails;
  final double totalOrderAmount;
  final double shippingFee;
  final double discount;
  final double tax;
  final int? sellerId;
  final int quantity;

  const CheckoutScreen({super.key, required this.cartList, this.fromProductDetails = false,
    required this.discount, required this.tax, required this.totalOrderAmount, required this.shippingFee,
    this.sellerId, required this.quantity});


  @override
  CheckoutScreenState createState() => CheckoutScreenState();
}

class CheckoutScreenState extends State<CheckoutScreen> {
  final GlobalKey<ScaffoldMessengerState> _scaffoldKey = GlobalKey<ScaffoldMessengerState>();
  final GlobalKey<FormState> passwordFormKey = GlobalKey<FormState>();

  final FocusNode _orderNoteNode = FocusNode();
  double _order = 0;
  double _tax = 0;
  late bool _billingAddress;
  bool _isSubmitting = false;

  DebounceHelper debounceHelper = DebounceHelper(milliseconds: 500);
  SplashController  splashController= Provider.of<SplashController>(Get.context!, listen: false);

  Map<String, Map<String, String>> _getUniqueStores(List<CartModel> cartList, SplashController splash) {
    final Map<String, Map<String, String>> stores = {};
    for (final item in cartList) {
      final String storeKey = (item.sellerIs == 'admin') ? 'admin' : (item.sellerId?.toString() ?? 'vendor_${item.shop?.id}');
      if (!stores.containsKey(storeKey)) {
        if (item.sellerIs == 'admin') {
          stores[storeKey] = {
            'name': splash.configModel?.inHouseShop?.name ?? 'Victorious Central Store',
            'address': splash.configModel?.inHouseShop?.address ?? 'Victorious Central Hub, Nigeria',
          };
        } else {
          stores[storeKey] = {
            'name': item.shop?.name ?? 'Vendor Store',
            'address': (item.shop?.address != null && item.shop!.address!.isNotEmpty) ? item.shop!.address! : 'Store Location',
          };
        }
      }
    }
    return stores;
  }

  @override
  void initState() {
    super.initState();
    Provider.of<AddressController>(context, listen: false).getAddressList();
    Provider.of<CartController>(context, listen: false).getCartData(context);
    Provider.of<CheckoutController>(context, listen: false).resetPaymentMethod();
    Provider.of<CheckoutController>(context, listen: false).setFulfillmentType(false, notify: false);
    Provider.of<CheckoutController>(context, listen: false).initDefaultPaymentMethod(
      splashController,
      isUpdate: false,
    );
    Provider.of<ShippingController>(context, listen: false).getChosenShippingMethod(context);
    // [AI] Victorious MARKET V1: COD and offline payments are decommissioned.
    // Digital payment via Paystack is canonical for doorstep delivery.
    // In-store pickup uses 24-hr stock hold reservation with payment at store inspection.

    if(Provider.of<CheckoutController>(context, listen: false).isAcceptTerms){
      Provider.of<CheckoutController>(context, listen: false).toggleTermsCheck(isUpdate: false);
    }

    _billingAddress = Provider.of<SplashController>(Get.context!, listen: false).configModel!.billingInputByCustomer == 1;
    Provider.of<CheckoutController>(context, listen: false).clearData();

    if(splashController.configModel?.systemTaxIncludeStatus != 1) {
      _tax = widget.tax;
    }

  }

  @override
  Widget build(BuildContext context) {
    _order = widget.totalOrderAmount + widget.discount;
    return Scaffold(
      resizeToAvoidBottomInset: true,
      key: _scaffoldKey,
      bottomNavigationBar: Consumer<AddressController>(
        builder: (context, locationProvider,_) {
          return Consumer<CheckoutController>(
            builder: (context, orderProvider, child) {
              if(splashController.configModel?.systemTaxIncludeStatus != 1) {
                _tax = CartHelper().calculateVatTax(Provider.of<CartController>(context, listen: false).cartList);
              }
              return Consumer<CartController>(
                builder: (context, cartProvider,_) {
                  return Consumer<ProfileController>(
                    builder: (context, profileProvider,_) {
                      return orderProvider.isLoading ? const Row(
                        mainAxisAlignment: MainAxisAlignment.center, children: [
                          SizedBox(width: 30,height: 30,child: CircularProgressIndicator())]
                      ) :

                      Container(
                        padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                        decoration: BoxDecoration(
                          color: Theme.of(context).cardColor,
                          borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: 0.06),
                              blurRadius: 12,
                              offset: const Offset(0, -4),
                            ),
                          ],
                        ),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [

                            const CheckoutConditionCheckBox(),
                            const SizedBox(height: Dimensions.paddingSizeSmall),

                            CustomButton(onTap: (orderProvider.isLoading || !orderProvider.isAcceptTerms || _isSubmitting) ? null : () async {
                              if(_isSubmitting) return;

                              // [AI] In-Shop Pickup Fulfillment Channel
                              if (orderProvider.isPickup) {
                                if (!Provider.of<AuthController>(context, listen: false).isLoggedIn()) {
                                  showCustomSnackBarWidget(
                                    getTranslated('login_to_reserve_pickup', context) ?? 'Please log in to make an in-store pickup reservation.',
                                    context,
                                    snackBarType: SnackBarType.warning,
                                  );
                                  return;
                                }
                                setState(() => _isSubmitting = true);
                                final cartIds = widget.cartList.map((e) => e.id!).toList();
                                final response = await orderProvider.submitPickupReservation(cartIds: cartIds, checkedOnly: false);
                                setState(() => _isSubmitting = false);

                                if (response.response != null && (response.response?.statusCode == 201 || response.response?.statusCode == 200)) {
                                  final parsed = PickupReservationResponse.fromJson(response.response!.data);
                                  Navigator.of(context).pushReplacement(
                                    MaterialPageRoute(
                                      builder: (_) => PickupReservationSuccessScreen(
                                        reservations: parsed.reservations ?? [],
                                      ),
                                    ),
                                  );
                                } else {
                                  showCustomSnackBarWidget(
                                    response.error?.toString() ?? 'Unable to create pickup reservation.',
                                    context,
                                    snackBarType: SnackBarType.error,
                                  );
                                }
                                return;
                              }

                              // [AI] Doorstep Delivery Channel (Requires Shipping Address + Paystack Gateway)
                              if(orderProvider.addressIndex == null) {
                                RouterHelper.getSavedAddressListRoute(fromGuest: !Provider.of<AuthController>(context, listen: false).isLoggedIn());
                                showCustomSnackBarWidget(getTranslated('select_a_shipping_address', context), Get.context!, snackBarType: SnackBarType.warning);
                              } else if(orderProvider.billingAddressIndex == null && _billingAddress && !orderProvider.sameAsBilling) {
                                RouterHelper.getSavedBillingAddressListRoute(fromGuest: !Provider.of<AuthController>(context, listen: false).isLoggedIn());
                                showCustomSnackBarWidget(getTranslated('select_a_billing_address', context), Get.context!, snackBarType: SnackBarType.warning);
                              } else {
                                if(!orderProvider.isCheckCreateAccount || (orderProvider.isCheckCreateAccount && (passwordFormKey.currentState?.validate() ?? false))) {
                                  setState(() => _isSubmitting = true);

                                  // [AI] Phase I: Two-Phase CheckoutIntent. Resolve canonical int address IDs.
                                  final int? addressId = orderProvider.addressIndex != null
                                      ? locationProvider.addressList![orderProvider.addressIndex!].id
                                      : null;
                                  final int? billingAddressId = (_billingAddress)
                                      ? !orderProvider.sameAsBilling
                                          ? locationProvider.addressList![orderProvider.billingAddressIndex!].id
                                          : locationProvider.addressList![orderProvider.addressIndex!].id
                                      : null;

                                  if (addressId == null) {
                                    setState(() => _isSubmitting = false);
                                    showCustomSnackBarWidget(getTranslated('select_a_shipping_address', context), Get.context!, snackBarType: SnackBarType.warning);
                                    return;
                                  }

                                  // [AI] POST /api/v1/checkout/intent → /api/v1/checkout/intent/{id}/pay → Paystack URL
                                  await orderProvider.placeDeliveryOrder(
                                    addressId: addressId,
                                    billingAddressId: billingAddressId,
                                    useCashback: orderProvider.isUseCashback,
                                  );
                                  setState(() => _isSubmitting = false);
                                }
                              }
                            },
                              buttonText: orderProvider.isPickup
                                  ? (getTranslated('reserve_store_pickup_pay_zero', context) ?? 'Reserve for Store Pickup (Pay ₦0.00 Now)')
                                  : '${getTranslated('proceed', context)}',
                            )
                          ],
                        ),
                      );
                    }
                  );
                }
              );
            }
          );
        }
      ),

      appBar: CustomAppBar(title: getTranslated('checkout', context)),
      body: Consumer<AuthController>(
        builder: (context, authProvider,_) {
          return Consumer<CheckoutController>(
            builder: (context, orderProvider,_) {
              return Column(children: [
                  Expanded(
                    child: ListView(
                      physics: const BouncingScrollPhysics(),
                      padding: const EdgeInsets.all(0),
                      children: [
                        const SizedBox(height: Dimensions.paddingSizeSmall),

                        // [AI] Fulfillment Channel Segmented Selector (Doorstep Delivery vs In-Shop Pickup)
                        Container(
                          margin: const EdgeInsets.symmetric(
                            horizontal: Dimensions.paddingSizeDefault,
                            vertical: Dimensions.paddingSizeExtraSmall,
                          ),
                          decoration: BoxDecoration(
                            color: Theme.of(context).cardColor,
                            borderRadius: BorderRadius.circular(16),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withValues(alpha: 0.04),
                                blurRadius: 10,
                                offset: const Offset(0, 4),
                              ),
                            ],
                            border: Border.all(
                              color: Theme.of(context).primaryColor.withValues(alpha: 0.08),
                            ),
                          ),
                          padding: const EdgeInsets.all(4),
                          child: Row(
                            children: [
                              Expanded(
                                child: InkWell(
                                  borderRadius: BorderRadius.circular(12),
                                  onTap: () {
                                    if (orderProvider.isPickup) {
                                      orderProvider.setFulfillmentType(false);
                                    }
                                  },
                                  child: AnimatedContainer(
                                    duration: const Duration(milliseconds: 200),
                                    padding: const EdgeInsets.symmetric(vertical: 10),
                                    decoration: BoxDecoration(
                                      color: !orderProvider.isPickup ? Theme.of(context).primaryColor : Colors.transparent,
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Column(
                                      children: [
                                        Row(
                                          mainAxisAlignment: MainAxisAlignment.center,
                                          children: [
                                            Icon(
                                              Icons.local_shipping_rounded,
                                              color: !orderProvider.isPickup ? Colors.white : Theme.of(context).hintColor,
                                              size: 18,
                                            ),
                                            const SizedBox(width: 6),
                                            Text(
                                              getTranslated('doorstep_delivery', context) ?? 'Doorstep Delivery',
                                              style: titilliumBold.copyWith(
                                                color: !orderProvider.isPickup ? Colors.white : Theme.of(context).textTheme.bodyLarge?.color,
                                                fontSize: Dimensions.fontSizeSmall,
                                              ),
                                            ),
                                          ],
                                        ),
                                        const SizedBox(height: 2),
                                        Text(
                                          getTranslated('courier_dispatch', context) ?? 'Rider to Your Door',
                                          style: titilliumRegular.copyWith(
                                            color: !orderProvider.isPickup ? Colors.white.withValues(alpha: 0.85) : Theme.of(context).hintColor,
                                            fontSize: Dimensions.fontSizeExtraSmall,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              ),
                              Expanded(
                                child: InkWell(
                                  borderRadius: BorderRadius.circular(12),
                                  onTap: () {
                                    if (!orderProvider.isPickup) {
                                      orderProvider.setFulfillmentType(true);
                                    }
                                  },
                                  child: AnimatedContainer(
                                    duration: const Duration(milliseconds: 200),
                                    padding: const EdgeInsets.symmetric(vertical: 10),
                                    decoration: BoxDecoration(
                                      color: orderProvider.isPickup ? Theme.of(context).primaryColor : Colors.transparent,
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Column(
                                      children: [
                                        Row(
                                          mainAxisAlignment: MainAxisAlignment.center,
                                          children: [
                                            Icon(
                                              Icons.storefront_rounded,
                                              color: orderProvider.isPickup ? Colors.white : Theme.of(context).hintColor,
                                              size: 18,
                                            ),
                                            const SizedBox(width: 6),
                                            Text(
                                              getTranslated('in_shop_pickup', context) ?? 'In-Shop Pickup',
                                              style: titilliumBold.copyWith(
                                                color: orderProvider.isPickup ? Colors.white : Theme.of(context).textTheme.bodyLarge?.color,
                                                fontSize: Dimensions.fontSizeSmall,
                                              ),
                                            ),
                                          ],
                                        ),
                                        const SizedBox(height: 2),
                                        Text(
                                          getTranslated('pay_zero_at_checkout', context) ?? 'Pay ₦0 Now • Free Hold',
                                          style: titilliumRegular.copyWith(
                                            color: orderProvider.isPickup ? Colors.white.withValues(alpha: 0.85) : const Color(0xFF10B981),
                                            fontSize: Dimensions.fontSizeExtraSmall,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),

                        const SizedBox(height: Dimensions.paddingSizeSmall),

                        // [AI] Fulfillment Specific Content:
                        // If In-Shop Pickup: Display store pickup location card(s) with shop name, address, no phone, and guidance button.
                        // If Doorstep Delivery: Display ShippingDetailsWidget (customer delivery & billing addresses).
                        if (orderProvider.isPickup) ...[
                          Container(
                            margin: const EdgeInsets.symmetric(
                              horizontal: Dimensions.paddingSizeDefault,
                              vertical: Dimensions.paddingSizeExtraSmall,
                            ),
                            padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                            decoration: BoxDecoration(
                              color: Theme.of(context).cardColor,
                              borderRadius: BorderRadius.circular(16),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withValues(alpha: 0.04),
                                  blurRadius: 10,
                                  offset: const Offset(0, 4),
                                ),
                              ],
                              border: Border.all(
                                color: Theme.of(context).primaryColor.withValues(alpha: 0.12),
                              ),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Icon(Icons.storefront_rounded, color: Theme.of(context).primaryColor, size: 20),
                                    const SizedBox(width: 8),
                                    Text(
                                      getTranslated('store_pickup_locations', context) ?? 'Store Pickup Locations',
                                      style: titilliumBold.copyWith(
                                        fontSize: Dimensions.fontSizeDefault,
                                        color: Theme.of(context).textTheme.bodyLarge?.color,
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: Dimensions.paddingSizeSmall),
                                ..._getUniqueStores(widget.cartList, splashController).entries.map((entry) {
                                  final store = entry.value;
                                  return Container(
                                    margin: const EdgeInsets.only(bottom: Dimensions.paddingSizeSmall),
                                    padding: const EdgeInsets.all(12),
                                    decoration: BoxDecoration(
                                      color: Theme.of(context).primaryColor.withValues(alpha: 0.04),
                                      borderRadius: BorderRadius.circular(12),
                                      border: Border.all(color: Theme.of(context).primaryColor.withValues(alpha: 0.1)),
                                    ),
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Row(
                                          children: [
                                            Icon(Icons.store, color: Theme.of(context).primaryColor, size: 18),
                                            const SizedBox(width: 6),
                                            Expanded(
                                              child: Text(
                                                store['name'] ?? 'Merchant Store',
                                                style: titilliumBold.copyWith(fontSize: Dimensions.fontSizeDefault),
                                              ),
                                            ),
                                          ],
                                        ),
                                        const SizedBox(height: 6),
                                        Row(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Icon(Icons.location_on, color: Theme.of(context).hintColor, size: 16),
                                            const SizedBox(width: 6),
                                            Expanded(
                                              child: Text(
                                                store['address'] ?? 'Store Physical Address',
                                                style: titilliumRegular.copyWith(
                                                  fontSize: Dimensions.fontSizeSmall,
                                                  color: Theme.of(context).hintColor,
                                                ),
                                              ),
                                            ),
                                          ],
                                        ),
                                        const SizedBox(height: 10),
                                        // Direction guidance note with message support action (STRICTLY NO PHONE NUMBER)
                                        Container(
                                          padding: const EdgeInsets.all(8),
                                          decoration: BoxDecoration(
                                            color: Theme.of(context).cardColor,
                                            borderRadius: BorderRadius.circular(8),
                                            border: Border.all(color: Theme.of(context).primaryColor.withValues(alpha: 0.15)),
                                          ),
                                          child: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              Row(
                                                children: [
                                                  Icon(Icons.directions_outlined, color: Theme.of(context).primaryColor, size: 16),
                                                  const SizedBox(width: 6),
                                                  Expanded(
                                                    child: Text(
                                                      getTranslated('pickup_direction_guidance', context) ??
                                                          'Need help finding this store? Message Customer Support for step-by-step guidance.',
                                                      style: titilliumRegular.copyWith(
                                                        fontSize: Dimensions.fontSizeExtraSmall,
                                                        color: Theme.of(context).textTheme.bodyMedium?.color,
                                                      ),
                                                    ),
                                                  ),
                                                ],
                                              ),
                                              const SizedBox(height: 6),
                                              InkWell(
                                                onTap: () => RouterHelper.getSupportTicketRoute(action: RouteAction.push),
                                                child: Container(
                                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                                  decoration: BoxDecoration(
                                                    color: Theme.of(context).primaryColor,
                                                    borderRadius: BorderRadius.circular(6),
                                                  ),
                                                  child: Row(
                                                    mainAxisSize: MainAxisSize.min,
                                                    children: [
                                                      const Icon(Icons.chat_bubble_outline, color: Colors.white, size: 12),
                                                      const SizedBox(width: 4),
                                                      Text(
                                                        getTranslated('message_support', context) ?? 'Message Support for Guidance',
                                                        style: titilliumSemiBold.copyWith(
                                                          fontSize: Dimensions.fontSizeExtraSmall,
                                                          color: Colors.white,
                                                        ),
                                                      ),
                                                    ],
                                                  ),
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                      ],
                                    ),
                                  );
                                }),
                              ],
                            ),
                          ),
                          const SizedBox(height: Dimensions.paddingSizeSmall),
                        ] else ...[
                          Padding(
                            padding: const EdgeInsets.only(bottom: Dimensions.paddingSizeDefault),
                            child: ShippingDetailsWidget(
                              hasPhysical: true,
                              billingAddress: _billingAddress,
                              passwordFormKey: passwordFormKey,
                            ),
                          ),
                        ],

                        if (Provider.of<AuthController>(context, listen: false).isLoggedIn() && !orderProvider.isPickup)
                          Padding(
                            padding: const EdgeInsets.only(bottom: Dimensions.paddingSizeSmall),
                            child: Consumer<ProfileController>(
                              builder: (context, profileProvider, _) {
                                final double loyaltyPoints = profileProvider.userInfoModel?.loyaltyPoint ?? 0;
                                return Container(
                                  decoration: BoxDecoration(
                                    color: Theme.of(context).cardColor,
                                    borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
                                    border: Border.all(
                                      color: orderProvider.isUseCashback
                                          ? Theme.of(context).primaryColor
                                          : Theme.of(context).cardColor,
                                      width: 1.5,
                                    ),
                                    boxShadow: [
                                      BoxShadow(
                                        color: Colors.black.withValues(alpha: 0.04),
                                        blurRadius: 8,
                                        offset: const Offset(0, 2),
                                      ),
                                    ],
                                  ),
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: Dimensions.paddingSizeDefault,
                                    vertical: Dimensions.paddingSizeSmall,
                                  ),
                                  child: Row(
                                    children: [
                                      Container(
                                        width: 40,
                                        height: 40,
                                        decoration: BoxDecoration(
                                          color: Theme.of(context).primaryColor.withValues(alpha: 0.1),
                                          shape: BoxShape.circle,
                                        ),
                                        child: Icon(
                                          Icons.stars_rounded,
                                          color: Theme.of(context).primaryColor,
                                          size: 24,
                                        ),
                                      ),
                                      const SizedBox(width: Dimensions.paddingSizeDefault),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              'Victorious Points (Cashback)',
                                              style: titilliumBold.copyWith(fontSize: Dimensions.fontSizeDefault),
                                            ),
                                            const SizedBox(height: 2),
                                            Text(
                                              loyaltyPoints > 0
                                                  ? 'Available: ${loyaltyPoints.toStringAsFixed(0)} pts (Redeem up to ${(splashController.configModel?.loyaltyPointMaxOrderRedemptionPercentage ?? 10.0).toStringAsFixed(0)}%)'
                                                  : 'No cashback points available',
                                              style: textRegular.copyWith(
                                                fontSize: Dimensions.fontSizeSmall,
                                                color: Theme.of(context).hintColor,
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                      Switch(
                                        value: orderProvider.isUseCashback && loyaltyPoints > 0,
                                        onChanged: loyaltyPoints > 0
                                            ? (val) {
                                                orderProvider.toggleUseCashback();
                                              }
                                            : null,
                                        activeColor: Theme.of(context).primaryColor,
                                      ),
                                    ],
                                  ),
                                );
                              },
                            ),
                          ),

                        // Payment Section: If Pickup, show In-Store inspection card; If Delivery, show ChoosePaymentWidget
                        if (orderProvider.isPickup) ...[
                          Container(
                            margin: const EdgeInsets.symmetric(
                              horizontal: Dimensions.paddingSizeDefault,
                              vertical: Dimensions.paddingSizeExtraSmall,
                            ),
                            padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                            decoration: BoxDecoration(
                              color: const Color(0xFF10B981).withValues(alpha: 0.08),
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: const Color(0xFF10B981).withValues(alpha: 0.25)),
                            ),
                            child: Row(
                              children: [
                                Container(
                                  width: 40,
                                  height: 40,
                                  decoration: const BoxDecoration(
                                    color: Color(0xFF10B981),
                                    shape: BoxShape.circle,
                                  ),
                                  child: const Icon(Icons.verified_rounded, color: Colors.white, size: 22),
                                ),
                                const SizedBox(width: Dimensions.paddingSizeDefault),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        getTranslated('pay_at_store_title', context) ?? 'Pay at Store Counter',
                                        style: titilliumBold.copyWith(
                                          fontSize: Dimensions.fontSizeDefault,
                                          color: const Color(0xFF065F46),
                                        ),
                                      ),
                                      const SizedBox(height: 2),
                                      Text(
                                        getTranslated('pay_at_store_desc', context) ??
                                            '₦0.00 due right now. Inspect items in person at the vendor shop before payment.',
                                        style: titilliumRegular.copyWith(
                                          fontSize: Dimensions.fontSizeSmall,
                                          color: const Color(0xFF047857),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: Dimensions.paddingSizeSmall),
                        ] else ...[
                          Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 0),
                            child: ChoosePaymentWidget(),
                          ),
                          const SizedBox(height: Dimensions.paddingSizeSmall),
                        ],

                        Container(
                          decoration: BoxDecoration(
                            color: Theme.of(context).cardColor,
                            borderRadius: BorderRadius.circular(16),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withValues(alpha: 0.04),
                                blurRadius: 10,
                                offset: const Offset(0, 4),
                              ),
                            ],
                            border: Border.all(
                              color: Theme.of(context).primaryColor.withValues(alpha: 0.08),
                            ),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Padding(
                                padding: const EdgeInsets.fromLTRB(
                                  Dimensions.paddingSizeDefault,
                                  Dimensions.paddingSizeDefault,
                                  Dimensions.paddingSizeDefault,
                                  Dimensions.paddingSizeSmall,
                                ),
                                child: Text(
                                  getTranslated('order_summary', context) ?? 'Order Summary',
                                  style: titilliumBold.copyWith(
                                    fontSize: Dimensions.fontSizeLarge,
                                    color: Theme.of(context).textTheme.bodyLarge?.color,
                                  ),
                                ),
                              ),
                              Padding(
                                padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                                child: Consumer<CheckoutController>(
                                  builder: (context, checkoutController, child) {
                                    return Consumer<ProfileController>(
                                      builder: (context, profileProvider, _) {
                                        double estimatedCashback = 0;
                                        if (checkoutController.isUseCashback && !checkoutController.isPickup) {
                                          final double userPoints = profileProvider.userInfoModel?.loyaltyPoint ?? 0;
                                          final double rate = (splashController.configModel?.loyaltyPointExchangeRate ?? 1).toDouble();
                                          final double maxRedeemPercent = (splashController.configModel?.loyaltyPointMaxOrderRedemptionPercentage ?? 10.0) / 100;
                                          final double maxCap = _order * maxRedeemPercent;
                                          final double pointsInNaira = userPoints * rate;
                                          estimatedCashback = (pointsInNaira > maxCap ? maxCap : pointsInNaira);
                                        }
                                        final double activeShipping = checkoutController.isPickup ? 0.0 : widget.shippingFee;
                                        final double totalPayable = (_order + activeShipping - widget.discount - estimatedCashback + _tax);

                                        return Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            widget.quantity > 1
                                            ? AmountWidget(
                                                title: '${getTranslated('sub_total', context)} ${' (${widget.quantity} ${getTranslated('items', context)}) '}',
                                                amount: PriceConverter.convertPrice(context, _order),
                                              )
                                            : AmountWidget(
                                                title: '${getTranslated('sub_total', context)} ${'(${widget.quantity} ${getTranslated('item', context)})'}',
                                                amount: PriceConverter.convertPrice(context, _order),
                                              ),
                                            AmountWidget(
                                              title: getTranslated('shipping_fee', context),
                                              amount: checkoutController.isPickup
                                                  ? (getTranslated('free_pickup', context) ?? '₦0.00 (In-Store Pickup)')
                                                  : PriceConverter.convertPrice(context, widget.shippingFee),
                                            ),
                                            AmountWidget(
                                              title: getTranslated('discount', context),
                                              amount: PriceConverter.convertPrice(context, widget.discount),
                                            ),

                                            if (checkoutController.isUseCashback && estimatedCashback > 0 && !checkoutController.isPickup)
                                            AmountWidget(
                                              title: 'Victorious Cashback',
                                              amount: '- ${PriceConverter.convertPrice(context, estimatedCashback)}',
                                            ),

                                            if (splashController.configModel?.systemTaxIncludeStatus != 1)
                                            AmountWidget(
                                              title: getTranslated('tax', context),
                                              amount: PriceConverter.convertPrice(context, _tax),
                                            ),

                                            Divider(height: 16, color: Theme.of(context).hintColor.withValues(alpha: 0.2)),
                                            if (checkoutController.isPickup) ...[
                                              AmountWidget(
                                                fontSize: Dimensions.fontSizeLarge, isTitleBlack: true,
                                                title: '${getTranslated('total_payable_now', context) ?? 'Total Payable Today'} ',
                                                amount: PriceConverter.convertPrice(context, 0),
                                              ),
                                              const SizedBox(height: 6),
                                              AmountWidget(
                                                title: '${getTranslated('due_at_store_inspection', context) ?? 'Due at Store Inspection'} ',
                                                amount: PriceConverter.convertPrice(
                                                  context,
                                                  (_order - widget.discount + _tax) > 0 ? (_order - widget.discount + _tax) : 0,
                                                ),
                                              ),
                                            ] else ...[
                                              AmountWidget(
                                                fontSize: Dimensions.fontSizeLarge, isTitleBlack: true,
                                                title: '${getTranslated('total_payable', context)} ${Provider.of<SplashController>(Get.context!, listen: false).configModel?.systemTaxIncludeStatus == 1 ? getTranslated('inc_vat_tax', context) : ''} ',
                                                amount: PriceConverter.convertPrice(context, totalPayable > 0 ? totalPayable : 0),
                                              ),
                                            ],

                                            const SizedBox(height: Dimensions.paddingSizeDefault),
                                          ],
                                        );
                                      },
                                    );
                                  },
                                ),
                              ),
                            ],
                          ),
                        ),


                        SizedBox(height: Dimensions.paddingSizeSmall),
                        Container(
                          decoration: BoxDecoration(
                            color: Theme.of(context).cardColor,
                            boxShadow: [BoxShadow(color: Theme.of(context).hintColor.withValues(alpha:0.2), spreadRadius:3, blurRadius: 3)],
                          ),
                          padding: const EdgeInsets.fromLTRB(
                            Dimensions.paddingSizeDefault,
                            Dimensions.paddingSizeDefault,
                            Dimensions.paddingSizeDefault,
                            Dimensions.paddingSizeDefault,
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(children: [
                                Text(
                                  '${getTranslated('order_note', context)}',
                                  style: textRegular.copyWith(
                                    fontSize: Dimensions.fontSizeLarge,
                                    color: Theme.of(context).textTheme.bodyLarge?.color,
                                  ),
                                ),
                              ]),
                              const SizedBox(height: Dimensions.paddingSizeSmall),
                              CustomTextFieldWidget(
                                hintText: getTranslated('enter_note', context),
                                inputType: TextInputType.multiline,
                                inputAction: TextInputAction.done,
                                maxLines: 3,
                                focusNode: _orderNoteNode,
                                controller: orderProvider.orderNoteController,
                              ),
                            ],
                          ),
                        ),

                        SizedBox(height: Dimensions.paddingSizeDefault),

                      ],
                    ),
                  ),
                ],
              );
            }
          );
        }
      ),
    );
  }

  void _callback(bool isSuccess, String message, String orderID, bool createAccount) async {
    setState(() => _isSubmitting = false);
    if(isSuccess) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        bool isLoggedIn = Provider.of<AuthController>(context, listen: false).isLoggedIn();
        String? orderId = Provider.of<CheckoutController>(context, listen: false).getFirstOrderId(orderID);

        if(isLoggedIn && orderId != null) {
          RouterHelper.getOrderScreenRoute(isBackButtonExist: true, action: RouteAction.push, fromPlaceOrder: true);
        } else {
          RouterHelper.getDashboardRoute(action: RouteAction.pushReplacement, page: 'home');
        }

        
        Future.delayed(Duration(milliseconds: 300), () {
          showModalBottomSheet(
            isDismissible: false,
            enableDrag: false,
            context: Get.context!,
            isScrollControlled: true,
            backgroundColor: Colors.transparent,
            shape: const RoundedRectangleBorder(
              borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
            ),
            builder: (context) {
              return Container(
                decoration: BoxDecoration(
                  color: Theme.of(context).cardColor,
                  borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
                ),
                child: OrderPlaceBottomSheetWidget(
                  orderID: orderID,
                  icon: Icons.check,
                  title: getTranslated(
                    createAccount
                      ? 'order_placed_Account_Created'
                      : 'order_placed',
                    Get.context!,
                  ),
                  description: getTranslated('your_order_placed', Get.context!),
                  isFailed: false,
                ),
              );
            },
          );
        });

      });
    }else {
      showCustomSnackBarWidget(message, context, snackBarType: SnackBarType.error);
    }
  }


}

