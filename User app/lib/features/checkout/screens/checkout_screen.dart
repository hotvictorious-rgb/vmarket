import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/features/address/controllers/address_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/domain/models/cart_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/controllers/checkout_controller.dart';
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


  @override
  void initState() {
    super.initState();
    Provider.of<AddressController>(context, listen: false).getAddressList();
    Provider.of<CartController>(context, listen: false).getCartData(context);
    Provider.of<CheckoutController>(context, listen: false).resetPaymentMethod();
    Provider.of<CheckoutController>(context, listen: false).initDefaultPaymentMethod(
      splashController,
      isUpdate: false,
    );
    Provider.of<ShippingController>(context, listen: false).getChosenShippingMethod(context);
    // [AI] Victorious MARKET V1: COD and offline payments are decommissioned.
    // Digital payment via Paystack is canonical.

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

                              if(orderProvider.addressIndex == null) {
                                RouterHelper.getSavedAddressListRoute(fromGuest: !Provider.of<AuthController>(context, listen: false).isLoggedIn());
                                showCustomSnackBarWidget(getTranslated('select_a_shipping_address', context), Get.context!, snackBarType: SnackBarType.warning);
                              } else if(orderProvider.billingAddressIndex == null && _billingAddress && !orderProvider.sameAsBilling) {
                                RouterHelper.getSavedBillingAddressListRoute(fromGuest: !Provider.of<AuthController>(context, listen: false).isLoggedIn());
                                showCustomSnackBarWidget(getTranslated('select_a_billing_address', context), Get.context!, snackBarType: SnackBarType.warning);
                              } else {
                                if(!orderProvider.isCheckCreateAccount || (orderProvider.isCheckCreateAccount && (passwordFormKey.currentState?.validate() ?? false))) {
                                  setState(() => _isSubmitting = true);
                                  String orderNote = orderProvider.orderNoteController.text.trim();

                                  String addressId =  orderProvider.addressIndex != null ?
                                  locationProvider.addressList![orderProvider.addressIndex!].id.toString() : '';

                                  String billingAddressId = (_billingAddress) ?
                                  !orderProvider.sameAsBilling ?
                                  locationProvider.addressList![orderProvider.billingAddressIndex!].id.toString() : locationProvider.addressList![orderProvider.addressIndex!].id.toString() : '';

                                  if(orderProvider.paymentMethodIndex != -1) {
                                    orderProvider.digitalPaymentPlaceOrder(
                                        orderNote: orderNote,
                                        customerId: Provider.of<AuthController>(context, listen: false).isLoggedIn() ?
                                        profileProvider.userInfoModel?.id.toString() : Provider.of<AuthController>(context, listen: false).getGuestToken(),
                                        addressId: addressId,
                                        billingAddressId: billingAddressId,
                                        useCashback: orderProvider.isUseCashback,
                                        paymentMethod: orderProvider.selectedDigitalPaymentMethodName);
                                  } else {
                                    setState(() => _isSubmitting = false);
                                    showModalBottomSheet(
                                      context: context, isScrollControlled: true, backgroundColor: Colors.transparent,
                                      builder: (c) {
                                        return PaymentMethodBottomSheetWidget();
                                      },
                                    );
                                  }
                                }
                              }
                            },
                              buttonText: '${getTranslated('proceed', context)}',
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
                        SizedBox(height: Dimensions.paddingSizeSmall),

                        Padding(
                          padding: const EdgeInsets.only(bottom: Dimensions.paddingSizeDefault),
                          child: ShippingDetailsWidget(
                            hasPhysical: true,
                            billingAddress: _billingAddress,
                            passwordFormKey: passwordFormKey,
                          ),
                        ),


                        if (Provider.of<AuthController>(context, listen: false).isLoggedIn())
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
                                                  ? 'Available: ${loyaltyPoints.toStringAsFixed(0)} pts (Redeem up to 10%)'
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


                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 0),
                          child: ChoosePaymentWidget(),
                        ),
                        SizedBox(height: Dimensions.paddingSizeSmall),

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
                                        if (checkoutController.isUseCashback) {
                                          final double userPoints = profileProvider.userInfoModel?.loyaltyPoint ?? 0;
                                          final double rate = (splashController.configModel?.loyaltyPointExchangeRate ?? 1).toDouble();
                                          final double maxCap = _order * 0.10;
                                          final double pointsInNaira = userPoints * rate;
                                          estimatedCashback = (pointsInNaira > maxCap ? maxCap : pointsInNaira);
                                        }
                                        final double totalPayable = (_order + widget.shippingFee - widget.discount - estimatedCashback + _tax);

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
                                              amount: PriceConverter.convertPrice(context, widget.shippingFee),
                                            ),
                                            AmountWidget(
                                              title: getTranslated('discount', context),
                                              amount: PriceConverter.convertPrice(context, widget.discount),
                                            ),

                                            if (checkoutController.isUseCashback && estimatedCashback > 0)
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
                                            AmountWidget(
                                              fontSize: Dimensions.fontSizeLarge, isTitleBlack: true,
                                              title: '${getTranslated('total_payable', context)} ${Provider.of<SplashController>(Get.context!, listen: false).configModel?.systemTaxIncludeStatus == 1 ? getTranslated('inc_vat_tax', context) : ''} ',
                                              amount: PriceConverter.convertPrice(context, totalPayable > 0 ? totalPayable : 0),
                                            ),

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

