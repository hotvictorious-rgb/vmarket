import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_snackbar_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/textfeild/custom_text_feild_widget.dart';
import 'package:sixvalley_vendor_app/features/dashboard/screens/dashboard_screen.dart';
import 'package:sixvalley_vendor_app/features/order/domain/models/order_model.dart';
import 'package:sixvalley_vendor_app/features/order_details/controllers/order_details_controller.dart';
import 'package:sixvalley_vendor_app/features/order_details/widgets/change_amount_widget.dart';
import 'package:sixvalley_vendor_app/features/order_details/widgets/order_details_shimmer.dart';
import 'package:sixvalley_vendor_app/features/order_details/widgets/order_payment_info_widget.dart';
import 'package:sixvalley_vendor_app/features/order_details/widgets/order_setup_bottom_sheet.dart';
import 'package:sixvalley_vendor_app/features/order_details/widgets/product_list_widget.dart';
import 'package:sixvalley_vendor_app/helper/color_helper.dart';
import 'package:sixvalley_vendor_app/helper/price_converter.dart';
import 'package:sixvalley_vendor_app/localization/language_constrants.dart';
import 'package:sixvalley_vendor_app/main.dart';
import 'package:sixvalley_vendor_app/features/order/controllers/order_controller.dart';
import 'package:sixvalley_vendor_app/features/splash/controllers/splash_controller.dart';
import 'package:sixvalley_vendor_app/utill/dimensions.dart';
import 'package:sixvalley_vendor_app/utill/images.dart';
import 'package:sixvalley_vendor_app/utill/styles.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_button_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_divider_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_image_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/image_diaglog_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/no_data_screen.dart';
import 'package:sixvalley_vendor_app/features/order_details/widgets/customer_contact_widget.dart';
import 'package:sixvalley_vendor_app/features/order_details/widgets/delivery_man_information_widget.dart';
import 'package:sixvalley_vendor_app/features/order_details/widgets/order_top_section_widget.dart';
import 'package:sixvalley_vendor_app/features/order_details/widgets/payment_status_widget.dart';
import 'package:sixvalley_vendor_app/features/order_details/widgets/shipping_and_biilling_widget.dart';
import 'package:sixvalley_vendor_app/features/order_details/widgets/third_party_delivery_info_widget.dart';


class OrderDetailsScreen extends StatefulWidget {
  final int? orderId;
  final bool fromNotification;
  const OrderDetailsScreen({super.key,  required this.orderId, this.fromNotification = false});

  @override
  State<OrderDetailsScreen> createState() => _OrderDetailsScreenState();
}

class _OrderDetailsScreenState extends State<OrderDetailsScreen> {
  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();
  void _loadData(BuildContext context) async {
    if(widget.fromNotification && Provider.of<SplashController>(Get.context!, listen: false).configModel == null) {
      await Provider.of<SplashController>(Get.context!, listen: false).initConfig();
    }
    Provider.of<OrderDetailsController>(Get.context!, listen: false).getOrderDetails(widget.orderId.toString());
     Provider.of<OrderDetailsController>(Get.context!, listen: false).initOrderStatusList();
  }


  @override
  void initState() {
    _loadData(context);
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: Navigator.canPop(context),
      onPopInvokedWithResult: (didPop, result) async {
        Provider.of<OrderDetailsController>(context, listen: false).emptyOrderDetails();
        if(widget.fromNotification) {
          Navigator.of(context).pushAndRemoveUntil(MaterialPageRoute(builder: (BuildContext context) => const DashboardScreen()), (route)=> false);
        } else {
          return;
        }
      },

      child: Scaffold(
        key: _scaffoldKey,
        appBar: AppBar(
          elevation: 1, backgroundColor: Theme.of(context).cardColor, toolbarHeight: 80,
          leadingWidth: 0, automaticallyImplyLeading: false,
          surfaceTintColor: Theme.of(context).highlightColor,
          title: Consumer<OrderDetailsController>(
            builder: (context, orderDetailsController,_) {
              final firstDetail = (orderDetailsController.orderDetails != null && orderDetailsController.orderDetails!.isNotEmpty)
                  ? orderDetailsController.orderDetails![0] : null;
              return OrderTopSectionWidget(orderModel: firstDetail?.order, fromNotification: widget.fromNotification);
            }
          ),
        ),

        body: RefreshIndicator(
          onRefresh: () async => _loadData(context),
          child: Consumer<OrderController>(
            builder:(context, orderController, child){
              return Consumer<OrderDetailsController>(
                builder: (context, orderDetailsController, child) {
                   final firstOrder = orderDetailsController.orderDetails?.isNotEmpty == true
                       ? orderDetailsController.orderDetails!.first.order
                       : null;
                   final tax = firstOrder?.totalTaxAmount ?? 0;
                   final productDiscount = firstOrder?.totalProductDiscount ?? 0;
                   final couponDiscount = firstOrder?.discountAmount ?? 0;
                   final shipping = firstOrder?.shippingCost ?? 0;
                   final isFreeShipping = firstOrder?.isShippingFree ?? false;
                   final referAndEarnDiscount = firstOrder?.referAndEarnDiscount ?? 0;
                   final extraDiscount = firstOrder?.extraDiscount ?? 0;
                   final authoritativeOrderAmount = firstOrder?.orderAmount ?? firstOrder?.initOrderAmount ?? 0;

                   return orderDetailsController.orderDetails != null ? orderDetailsController.orderDetails!.isNotEmpty ?
                  CustomScrollView(slivers: [
                    SliverToBoxAdapter(child: Column(children: [
                      Container(height: 10, color: Theme.of(context).primaryColor.withValues(alpha:.1)),

                      Container(decoration: BoxDecoration(color: Theme.of(context).cardColor, boxShadow: ThemeShadow.getShadow(context)),
                        child: Column(crossAxisAlignment: CrossAxisAlignment.start,children: [

                          (() {
                            final String currentStatus = orderDetailsController.orderDetails?[0].order?.orderStatus ?? '';
                            final bool isSelfPickupOrder = orderDetailsController.orderDetails?[0].order?.orderType == 'pickup'
                                || orderDetailsController.orderDetails?[0].order?.deliveryType == 'self_pickup'
                                || (orderDetailsController.orderDetails?[0].order?.shipping?.title?.toLowerCase().contains('pickup') ?? false);
                            final bool isOrderPaid = orderDetailsController.orderDetails?[0].order?.paymentStatus == 'paid';

                            if (currentStatus == 'ready_for_pickup' || (isSelfPickupOrder && currentStatus == 'processing')) {
                              return Container(
                                margin: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault, vertical: Dimensions.paddingSizeExtraSmall),
                                padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                                decoration: BoxDecoration(
                                  color: isSelfPickupOrder
                                      ? (isOrderPaid ? const Color(0xFFE0F2F1) : const Color(0xFFFFF3E0))
                                      : const Color(0xFFFFF8E1),
                                  borderRadius: BorderRadius.circular(12),
                                  border: Border.all(
                                    color: isSelfPickupOrder
                                        ? (isOrderPaid ? const Color(0xFF00897B) : const Color(0xFFFB8C00))
                                        : const Color(0xFFFFA000),
                                    width: 1.2,
                                  ),
                                ),
                                child: Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Icon(
                                      isSelfPickupOrder
                                          ? (isOrderPaid ? Icons.storefront_rounded : Icons.lock_clock_rounded)
                                          : Icons.two_wheeler_rounded,
                                      color: isSelfPickupOrder
                                          ? (isOrderPaid ? const Color(0xFF00897B) : const Color(0xFFE65100))
                                          : const Color(0xFFD97706),
                                      size: 26,
                                    ),
                                    const SizedBox(width: 10),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            isSelfPickupOrder
                                                ? (isOrderPaid ? (getTranslated('customer_pickup_ready_title', context) ?? 'Ready for Customer In-Store Pickup') : (getTranslated('payment_pending_pickup', context) ?? 'Ready for Pickup (Payment Pending)'))
                                                : (getTranslated('rider_pickup_ready_title', context) ?? 'Ready for Victorious Delivery Pickup'),
                                            style: robotoBold.copyWith(
                                              color: isSelfPickupOrder
                                                  ? (isOrderPaid ? const Color(0xFF00695C) : const Color(0xFFE65100))
                                                  : const Color(0xFFB45309),
                                              fontSize: Dimensions.fontSizeDefault,
                                            ),
                                          ),
                                          const SizedBox(height: 4),
                                          Text(
                                            isSelfPickupOrder
                                                ? (isOrderPaid
                                                    ? (getTranslated('customer_pickup_ready_paid_desc', context) ?? 'Customer payment confirmed. Handover requires customer 6-digit Secret Pickup OTP.')
                                                    : (getTranslated('customer_pickup_ready_unpaid_desc', context) ?? 'Order prepared. Handover OTP verification is locked until payment is verified by Victorious MARKET.'))
                                                : (getTranslated('rider_pickup_ready_desc', context) ?? 'Order packaged. Hand parcel to Victorious Delivery rider upon 6-digit Rider Pickup OTP verification.'),
                                            style: robotoRegular.copyWith(
                                              fontSize: Dimensions.fontSizeSmall,
                                              color: Theme.of(context).textTheme.bodyMedium?.color?.withValues(alpha: 0.8),
                                            ),
                                          ),
                                          if (isSelfPickupOrder && isOrderPaid && orderDetailsController.orderDetails?[0].order?.id != null) ...[
                                            const SizedBox(height: 10),
                                            InkWell(
                                              onTap: () => _showVerifyPickupOtpDialog(
                                                context,
                                                orderDetailsController.orderDetails![0].order!.id!,
                                                orderDetailsController,
                                              ),
                                              child: Container(
                                                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                                                decoration: BoxDecoration(
                                                  color: const Color(0xFF00897B),
                                                  borderRadius: BorderRadius.circular(8),
                                                  boxShadow: [
                                                    BoxShadow(
                                                      color: const Color(0xFF00897B).withValues(alpha: 0.25),
                                                      blurRadius: 4,
                                                      offset: const Offset(0, 2),
                                                    ),
                                                  ],
                                                ),
                                                child: Row(
                                                  mainAxisSize: MainAxisSize.min,
                                                  children: [
                                                    const Icon(Icons.verified_user_rounded, color: Colors.white, size: 16),
                                                    const SizedBox(width: 6),
                                                    Text(
                                                      getTranslated('verify_customer_pickup_otp', context) ?? 'Verify Customer OTP',
                                                      style: robotoBold.copyWith(color: Colors.white, fontSize: Dimensions.fontSizeSmall),
                                                    ),
                                                  ],
                                                ),
                                              ),
                                            ),
                                          ],
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                              );
                            }
                            return const SizedBox();
                          })(),

                          OrderPaymentInfoWidget(),
                          const SizedBox(height: Dimensions.paddingSizeSmall),

                          orderDetailsController.orderDetails![0].order!.orderType == 'POS' ? const SizedBox():
                          ShippingAndBillingWidget(orderModel: orderDetailsController.orderDetails![0].order!, orderType: orderDetailsController.orderDetails![0].order!.orderType!),

                          if(orderDetailsController.orderDetails![0].order!.orderType != 'POS')
                          const SizedBox(height: Dimensions.paddingSizeSmall),

                          Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            ProductListWidget(orderId: widget.orderId ?? 0),
                            const SizedBox(height: Dimensions.paddingSizeSmall),


                            CustomerContactWidget(orderModel: orderDetailsController.orderDetails![0].order),
                            const SizedBox(height: Dimensions.paddingSizeSmall),

                            orderDetailsController.orderDetails![0].order!.deliveryMan != null?
                            Padding(padding: const EdgeInsets.only(bottom: Dimensions.paddingSizeSmall),
                              child: DeliveryManContactInformationWidget(orderModel: orderDetailsController.orderDetails![0].order, orderType: orderDetailsController.orderDetails![0].order!.orderType),
                            ):const SizedBox(),

                            // Container(padding: const EdgeInsets.fromLTRB(Dimensions.paddingSizeDefault,
                            //     Dimensions.paddingSizeDefault, Dimensions.paddingSizeDefault, 0),
                            //   child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            //     Row(children: [
                            //       SizedBox(width: 15, child: Image.asset(Images.orderSummery)),
                            //       const SizedBox(width: Dimensions.paddingSizeExtraSmall),
                            //       Text(getTranslated('order_summery', context)!,
                            //         style: titilliumSemiBold.copyWith(fontSize: Dimensions.fontSizeLarge)),
                            //     ],
                            //     ),
                            //     const SizedBox(height: Dimensions.paddingSizeDefault,),
                            //
                            //     ListView.builder(
                            //       padding: const EdgeInsets.all(0),
                            //       shrinkWrap: true,
                            //       physics: const NeverScrollableScrollPhysics(),
                            //       itemCount: orderDetailsController.orderDetails!.length,
                            //       itemBuilder: (context, index) {
                            //         return OrderedProductListItemWidget(orderDetailsModel: orderDetailsController.orderDetails![index],
                            //           paymentStatus: orderController.paymentStatus,orderId: widget.orderId,
                            //           index: index, length: orderDetailsController.orderDetails!.length,
                            //         );
                            //       },
                            //     ),
                            //   ],
                            //   ),
                            // ),


                            /// bolling cummery section
                            Container(
                              padding: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeDefault),
                              decoration: BoxDecoration(
                                color: Theme.of(context).cardColor,
                                boxShadow: [BoxShadow(color: Theme.of(context).hintColor.withValues(alpha:0.2), spreadRadius:1.5, blurRadius: 3)],
                              ),

                              child: Column(children: [
                                Padding(
                                  padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                                  child: Row(
                                    children: [
                                      Text(
                                        getTranslated('billing_summery', context)!,
                                        style: robotoBold.copyWith(
                                            fontSize: Dimensions.fontSizeLarge,
                                            color: Theme.of(context).textTheme.bodyLarge?.color
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                SizedBox(height: Dimensions.paddingSizeSmall),

                                Divider(thickness: 0.2, height: 1, color: Theme.of(context).hintColor.withValues(alpha: .65)),
                                SizedBox(height: Dimensions.paddingSizeSmall),

                                Padding(
                                  padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                                  child: Column(
                                    children: [

                                      orderDetailsController.orderDetails![0].order!.editedStatus == 1 ?
                                      Container(
                                        padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeSmall, vertical: Dimensions.paddingSizeExtraSmall),
                                        decoration: BoxDecoration(
                                          color: Theme.of(context).colorScheme.tertiary.withValues(alpha: 0.1),
                                          borderRadius: BorderRadius.circular(Dimensions.radiusSmall),
                                        ),
                                        child: Row(
                                          children: [
                                            Icon(Icons.info, color: Theme.of(context).colorScheme.tertiary, size: Dimensions.iconSizeSmall),
                                            SizedBox(width: Dimensions.paddingSizeExtraSmall),

                                            Text(
                                                getTranslated('total_bill_has_been_updated_after', context) ?? '',
                                                style: titilliumRegular.copyWith(color: Theme.of(context).textTheme.headlineLarge!.color, fontSize: Dimensions.fontSizeSmall)
                                            )
                                          ],
                                        ),
                                      ) : SizedBox(),


                                      orderDetailsController.orderDetails![0].order!.editedStatus == 1 ?
                                      SizedBox(height: Dimensions.paddingSizeSmall) : SizedBox(),


                                       Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                                        Text(getTranslated('tax', context)!,
                                            style: titilliumRegular.copyWith(
                                                color: ColorHelper.blendColors(Colors.white, Theme.of(context).textTheme.bodyLarge!.color!, 0.7))
                                        ),

                                        Text(' + ${PriceConverter.convertPrice(context, tax)}',
                                            style: titilliumRegular.copyWith(
                                                color: ColorHelper.blendColors(Colors.white, Theme.of(context).textTheme.bodyLarge!.color!, 0.7))),]),
                                      const SizedBox(height: Dimensions.paddingSizeSmall,),


                                      if (productDiscount > 0)
                                        Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                                          Text(getTranslated('discount', context)!,
                                            style: titilliumRegular.copyWith(
                                                color: ColorHelper.blendColors(Colors.white, Theme.of(context).textTheme.bodyLarge!.color!, 0.7))),


                                        Text('- ${PriceConverter.convertPrice(context, productDiscount)}',
                                            style: titilliumRegular.copyWith(
                                              color: ColorHelper.blendColors(Colors.white, Theme.of(context).textTheme.bodyLarge!.color!, 0.7),
                                            )),]),
                                      if (productDiscount > 0)
                                        const SizedBox(height: Dimensions.paddingSizeSmall),

                                      if (couponDiscount > 0)
                                        Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                                          Text(getTranslated('coupon_discount', context)!,
                                              style: titilliumRegular.copyWith(
                                                  color: ColorHelper.blendColors(Colors.white, Theme.of(context).textTheme.bodyLarge!.color!, 0.7))),
                                          Text('- ${PriceConverter.convertPrice(context, couponDiscount)}',
                                              style: titilliumRegular.copyWith(
                                                  color: ColorHelper.blendColors(Colors.white, Theme.of(context).textTheme.bodyLarge!.color!, 0.7))),
                                        ]),
                                      if (couponDiscount > 0)
                                        const SizedBox(height: Dimensions.paddingSizeSmall),

                                      if(referAndEarnDiscount > 0)
                                         Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                                          Text(getTranslated('referral_discount', context)!,
                                              style: titilliumRegular.copyWith(
                                                  color: ColorHelper.blendColors(Colors.white, Theme.of(context).textTheme.bodyLarge!.color!, 0.7))
                                          ),

                                          Text('- ${PriceConverter.convertPrice(context, referAndEarnDiscount)}',
                                              style: titilliumRegular.copyWith(
                                                color: ColorHelper.blendColors(Colors.white, Theme.of(context).textTheme.bodyLarge!.color!, 0.7),
                                              )),
                                        ]),

                                      if(referAndEarnDiscount > 0)
                                        const SizedBox(height: Dimensions.paddingSizeSmall,),


                                      orderDetailsController.orderDetails![0].order!.orderType == "POS"?
                                      Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                                        Text(getTranslated('extra_discount', context)!,
                                            style: titilliumRegular.copyWith(
                                                color: ColorHelper.blendColors(Colors.white, Theme.of(context).textTheme.bodyLarge!.color!, 0.7))),
                                         Text('- ${PriceConverter.convertPrice(context, extraDiscount)}',
                                            style: titilliumRegular.copyWith(
                                                color: ColorHelper.blendColors(Colors.white, Theme.of(context).textTheme.bodyLarge!.color!, 0.7))),
                                      ]):const SizedBox(),
                                      SizedBox(height:  orderDetailsController.orderDetails![0].order!.orderType == "POS"? Dimensions.paddingSizeSmall: 0),


                                       Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                                        Text(getTranslated('shipping_fee', context)! + _shippingFreeText(orderDetailsController.orderDetails![0].order),
                                            style: titilliumRegular.copyWith(
                                                color: ColorHelper.blendColors(Colors.white, Theme.of(context).textTheme.bodyLarge!.color!, 0.7))),
                                        Text('${(isFreeShipping) ? '' : '+'} ${PriceConverter.convertPrice(context, shipping)}',
                                            style: titilliumRegular.copyWith(
                                                color: ColorHelper.blendColors(Colors.white, Theme.of(context).textTheme.bodyLarge!.color!, 0.7))),]),

                                      const Padding(padding: EdgeInsets.symmetric(vertical: Dimensions.paddingSizeSmall),
                                          child: CustomDividerWidget()),


                                      Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                                        Text(getTranslated('total_amount', context)!,
                                            style: titilliumSemiBold.copyWith(fontSize: Dimensions.fontSizeDefault)
                                        ),
                                         Text(PriceConverter.convertPrice(context, authoritativeOrderAmount),
                                          style: titilliumSemiBold.copyWith(fontSize: Dimensions.fontSizeExtraLarge,
                                              color: Theme.of(context).primaryColor),
                                        ),
                                      ]),

                                       if (orderDetailsController.orderDetails![0].order!.orderType == 'POS')
                                         Column(
                                           children: [
                                             const SizedBox(height: Dimensions.paddingSizeSmall),
                                             Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                                               Text(getTranslated('paid_amount', context)!,
                                                   style: titilliumRegular.copyWith(
                                                       color: ColorHelper.blendColors(Colors.white, Theme.of(context).textTheme.bodyLarge!.color!, 0.7))),
                                               Text(PriceConverter.convertPrice(context, firstOrder?.paidAmount ?? 0),
                                                   style: titilliumRegular.copyWith(
                                                       color: ColorHelper.blendColors(Colors.white, Theme.of(context).textTheme.bodyLarge!.color!, 0.7))),]
                                             ),
                                           ],
                                         ),

                                    ],
                                  ),
                                ),

                              ],),
                            ),
                            SizedBox(height: Dimensions.paddingSizeSmall),


                            orderDetailsController.orderDetails![0].order!.orderNote != null && orderDetailsController.orderDetails![0].order!.orderNote!.trim().isNotEmpty ?
                            Container(decoration: BoxDecoration(
                              color: Theme.of(context).cardColor,
                              boxShadow: [BoxShadow(color: Theme.of(context).hintColor.withValues(alpha:.25),spreadRadius: .11,blurRadius: .11, offset: const Offset(0,2))],
                              borderRadius: const BorderRadius.only(bottomLeft: Radius.circular(Dimensions.paddingSizeSmall),
                                bottomRight: Radius.circular(Dimensions.paddingSizeSmall))),
                              child: Container(decoration: BoxDecoration(
                                color: Theme.of(context).hintColor.withValues(alpha:.07),
                                borderRadius: const BorderRadius.only(bottomLeft: Radius.circular(Dimensions.paddingSizeSmall),
                                    bottomRight: Radius.circular(Dimensions.paddingSizeSmall)),),
                                padding: const EdgeInsets.fromLTRB( Dimensions.paddingSizeDefault,Dimensions.paddingSizeDefault,Dimensions.paddingSizeDefault,
                                  Dimensions.paddingSizeDefault),

                                child: Column(crossAxisAlignment: CrossAxisAlignment.start,children: [
                                  Row(children: [
                                    Padding(padding: const EdgeInsets.only(right: Dimensions.paddingSizeSmall),
                                      child: Image.asset(Images.orderNote,color: Theme.of(context).textTheme.bodyLarge?.color, width: Dimensions.iconSizeSmall ),),
                                    Text(getTranslated('order_note', context)!, style: titilliumSemiBold.copyWith(fontSize: Dimensions.fontSizeLarge,
                                      color: ColorHelper.blendColors(Colors.white, Theme.of(context).textTheme.bodyLarge!.color!, 0.7),)),
                                  ]),
                                  const SizedBox(height: Dimensions.paddingSizeExtraSmall),

                                  Text(orderDetailsController.orderDetails![0].order!.orderNote != null? orderDetailsController.orderDetails![0].order!.orderNote ?? '': "",
                                      style: titilliumRegular.copyWith(color: Theme.of(context).textTheme.bodyLarge?.color)),
                                ]),
                              ),
                            ):const SizedBox(),
                          ]),


                          PaymentStatusWidget(order: orderController, orderModel: orderDetailsController.orderDetails![0].order!, orderDetailsModel: orderDetailsController.orderDetails![0]),


                          ChangeAmountWidget(
                            amount: orderDetailsController.orderDetails![0].order?.bringCashAmount ?? 0,
                            currency: orderDetailsController.orderDetails![0].order?.bringChangeAmountCurrency ?? '',
                          ),


                          orderDetailsController.orderDetails![0].order!.thirdPartyServiceName != null?
                          Padding(padding: const EdgeInsets.only(bottom: Dimensions.paddingSizeSmall),
                            child: ThirdPartyDeliveryInfoWidget(orderModel: orderDetailsController.orderDetails![0].order),
                          ) : const SizedBox.shrink(),

                          if(orderDetailsController.orderDetails != null && orderDetailsController.orderDetails![0].verificationImages != null && orderDetailsController.orderDetails![0].verificationImages!.isNotEmpty)
                            Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                              Padding(padding: const EdgeInsets.fromLTRB(Dimensions.paddingSizeSmall,  Dimensions.paddingSizeSmall, Dimensions.paddingSizeSmall, Dimensions.paddingSizeSmall),
                                child: Text('${getTranslated('completed_service_picture', context)}',
                                  style: robotoMedium.copyWith(fontSize: Dimensions.fontSizeDefault, color: Theme.of(context).hintColor),)
                              ),

                              SizedBox(height: 120,
                                child: ListView.builder(
                                  itemCount: orderDetailsController.orderDetails![0].verificationImages?.length,
                                  scrollDirection: Axis.horizontal,
                                  itemBuilder: (context, index){

                                    return InkWell(onTap: () => showDialog(context: context, builder: (_)=> ImageDialogWidget(imageUrl: '${orderDetailsController.orderDetails![0].verificationImages?[index].imageFullUrl?.path}')),
                                      child: Padding(padding:  EdgeInsets.only(left: Dimensions.paddingSizeSmall,
                                          right: orderDetailsController.orderDetails![0].verificationImages!.length == index+1? Dimensions.paddingSizeSmall : 0),
                                        child: SizedBox(width: 200,
                                          child: ClipRRect(borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall),
                                            child: Container(decoration: BoxDecoration(
                                              border: Border.all(color: Theme.of(context).primaryColor.withValues(alpha:.25), width: .25),
                                              borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall)),
                                              child: CustomImageWidget(image: '${orderDetailsController.orderDetails![0].verificationImages?[index].imageFullUrl?.path}')
                                            ),
                                          ),
                                        ),
                                      ),
                                    );
                                  }),
                              ),
                            ],
                            ),



                        ])),
                        const SizedBox(height: Dimensions.paddingSizeSmall),
                      ],
                    ))
                  ],
                  ) : const NoDataScreen() :
                  const OrderDetailsShimmer();
                }
              );
            }
          ),
        ),

        bottomNavigationBar: Consumer<OrderDetailsController>(builder: (_, orderDetailsController, __) {
          if (orderDetailsController.orderDetails?.isEmpty ?? true) return const SizedBox();
          final order = orderDetailsController.orderDetails?[0].order;
          if (order?.orderType == 'POS') return const SizedBox();

          final String status = order?.orderStatus ?? '';
          final bool isTerminal = ['delivered', 'canceled', 'returned', 'failed'].contains(status);
          final bool isCustomerSelfPickup = order?.orderType == 'pickup'
              || order?.deliveryType == 'self_pickup'
              || (order?.shipping?.title?.toLowerCase().contains('pickup') ?? false);
          final bool isPaid = order?.paymentStatus == 'paid';

          String? quickActionText;
          Color actionBgColor = Theme.of(context).primaryColor;
          VoidCallback? onActionTap;

          if (status == 'pending') {
            quickActionText = getTranslated('confirm_order', context) ?? 'Confirm Order';
            onActionTap = () async {
              if (order?.id != null) {
                await orderDetailsController.updateQuickOrderStatus(order!.id!, 'confirmed');
              }
            };
          } else if (status == 'confirmed') {
            quickActionText = getTranslated('mark_as_preparing', context) ?? 'Mark as Preparing';
            onActionTap = () async {
              if (order?.id != null) {
                await orderDetailsController.updateQuickOrderStatus(order!.id!, 'processing');
              }
            };
          } else if (status == 'processing') {
            quickActionText = getTranslated('mark_ready_for_pickup', context) ?? 'Mark Ready for Pickup';
            actionBgColor = const Color(0xFF00897B);
            onActionTap = () async {
              if (order?.id != null) {
                await orderDetailsController.updateQuickOrderStatus(order!.id!, 'ready_for_pickup');
              }
            };
          } else if (status == 'ready_for_pickup') {
            if (isCustomerSelfPickup) {
              if (isPaid) {
                quickActionText = getTranslated('verify_customer_pickup_otp', context) ?? 'Verify Pickup OTP';
                actionBgColor = const Color(0xFF00897B);
                onActionTap = () => _showVerifyPickupOtpDialog(context, order!.id!, orderDetailsController);
              } else {
                quickActionText = getTranslated('payment_pending_pickup', context) ?? 'Payment Pending (Locked)';
                actionBgColor = Colors.grey.shade400;
                onActionTap = () {
                  showCustomSnackBarWidget(
                    'Handover OTP verification is locked until customer payment is confirmed by Victorious MARKET.',
                    context,
                    isError: true,
                  );
                };
              }
            } else {
              quickActionText = getTranslated('ready_for_pickup', context) ?? 'Ready for Rider';
              actionBgColor = const Color(0xFFD97706);
              onActionTap = () {
                showCustomSnackBarWidget(
                  getTranslated('rider_pickup_ready_desc', context) ?? 'Order packaged. Hand parcel to Victorious Delivery rider upon 6-digit Rider Pickup OTP verification.',
                  context,
                  isError: false,
                  sanckBarType: SnackBarType.success,
                );
              };
            }
          }

          return Container(
            decoration: BoxDecoration(color: Theme.of(context).cardColor, boxShadow: ThemeShadow.getShadow(context)),
            padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault, vertical: Dimensions.paddingSizeSmall),
            child: Row(
              children: [
                if (quickActionText != null && !isTerminal)
                  Expanded(
                    flex: 2,
                    child: CustomButtonWidget(
                      backgroundColor: actionBgColor,
                      borderRadius: Dimensions.paddingSizeExtraSmall,
                      btnTxt: quickActionText,
                      onTap: onActionTap,
                    ),
                  ),
                if (quickActionText != null && !isTerminal)
                  const SizedBox(width: Dimensions.paddingSizeSmall),
                Expanded(
                  flex: (quickActionText != null && !isTerminal) ? 1 : 2,
                  child: InkWell(
                    onTap: () {
                      showModalBottomSheet(
                        backgroundColor: Theme.of(context).cardColor,
                        useSafeArea: true,
                        shape: const RoundedRectangleBorder(
                          borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
                        ),
                        isScrollControlled: true,
                        context: context,
                        builder: (BuildContext context) {
                          return OrderSetupBottomSheet(
                            orderModel: order,
                            bottomContext: context,
                          );
                        },
                      );
                    },
                    child: Container(
                      height: 45,
                      alignment: Alignment.center,
                      decoration: BoxDecoration(
                        color: Theme.of(context).cardColor,
                        borderRadius: BorderRadius.circular(Dimensions.paddingSizeExtraSmall),
                        border: Border.all(color: Theme.of(context).primaryColor),
                      ),
                      child: Text(
                        (quickActionText != null && !isTerminal) ? (getTranslated('more_options', context) ?? 'Setup') : (getTranslated('order_setup', context) ?? 'Order Setup'),
                        style: robotoMedium.copyWith(
                          color: Theme.of(context).primaryColor,
                          fontSize: Dimensions.fontSizeDefault,
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          );
        }),
      ),
    );
  }

  void _showVerifyPickupOtpDialog(BuildContext context, int orderId, OrderDetailsController controller) {
    final TextEditingController otpController = TextEditingController();
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: Row(
            children: [
              const Icon(Icons.verified_user_rounded, color: Color(0xFF00897B), size: 28),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  getTranslated('enter_customer_pickup_otp', ctx) ?? 'Enter Customer Pickup OTP',
                  style: robotoBold.copyWith(fontSize: Dimensions.fontSizeLarge),
                ),
              ),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                getTranslated('customer_pickup_ready_paid_desc', ctx) ?? 'Customer payment is confirmed. Collect the 6-digit Secret Pickup OTP from customer to finalize handover.',
                style: robotoRegular.copyWith(fontSize: Dimensions.fontSizeSmall, color: Theme.of(ctx).hintColor),
              ),
              const SizedBox(height: 16),
              CustomTextFieldWidget(
                controller: otpController,
                hintText: getTranslated('pickup_otp_hint', ctx) ?? 'e.g. 123456',
                textInputType: TextInputType.number,
                isAmount: false,
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(ctx).pop(),
              child: Text(getTranslated('cancel', ctx) ?? 'Cancel', style: robotoMedium.copyWith(color: Theme.of(ctx).hintColor)),
            ),
            Consumer<OrderDetailsController>(
              builder: (_, detailsController, __) {
                return detailsController.isPickupVerifying
                    ? const Padding(
                        padding: EdgeInsets.symmetric(horizontal: 16),
                        child: SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)),
                      )
                    : ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF00897B),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        ),
                        onPressed: () async {
                          final String otp = otpController.text.trim();
                          if (otp.length != 6) {
                            showCustomSnackBarWidget('Please enter a valid 6-digit OTP', ctx, isError: true);
                            return;
                          }
                          final bool success = await controller.verifyCustomerPickupOtp(
                            orderId: orderId,
                            pickupOtp: otp,
                            context: ctx,
                          );
                          if (success && ctx.mounted) {
                            Navigator.of(ctx).pop();
                          }
                        },
                        child: Text(
                          getTranslated('verify_and_handover', ctx) ?? 'Verify & Handover',
                          style: robotoBold.copyWith(color: Colors.white),
                        ),
                      );
              },
            ),
          ],
        );
      },
    );
  }


  String _shippingFreeText(Order? order) {
    if((order?.isShippingFree ?? false) && order?.shippingResponsibility == 'inhouse_shipping') {
      return ' (${getTranslated('expense_bearer_admin', context)})';
    } else if ((order?.isShippingFree ?? false) && order?.shippingResponsibility == 'sellerwise_shipping' ) {
      return ' (${getTranslated('expense_bearer_vendor', context)})';
    }
    return '';
  }

}
