import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_asset_image_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_confirmation_dialog_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_dialog_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_snackbar_widget.dart';
import 'package:sixvalley_vendor_app/features/dashboard/screens/dashboard_screen.dart';
import 'package:sixvalley_vendor_app/features/order/domain/models/order_model.dart';
import 'package:sixvalley_vendor_app/features/order_details/controllers/order_details_controller.dart';
import 'package:sixvalley_vendor_app/features/order_edit/screens/edit_product_screen.dart';
import 'package:sixvalley_vendor_app/features/splash/controllers/splash_controller.dart';
import 'package:sixvalley_vendor_app/localization/language_constrants.dart';
import 'package:sixvalley_vendor_app/utill/app_constants.dart';
import 'package:sixvalley_vendor_app/utill/dimensions.dart';
import 'package:sixvalley_vendor_app/utill/images.dart';
import 'package:sixvalley_vendor_app/utill/styles.dart';
import '../../../main.dart';


class OrderTopSectionWidget extends StatelessWidget {
  final Order? orderModel;
  final bool? fromNotification;
  const OrderTopSectionWidget({super.key, this.orderModel, this.fromNotification});

  @override
  Widget build(BuildContext context) {
    if (orderModel == null) {
      return Stack(
        children: [
          Center(
            child: Text(
              getTranslated('order_details', context) ?? 'Order Details',
              style: robotoBold.copyWith(fontSize: Dimensions.fontSizeLarge, color: Theme.of(context).textTheme.bodyLarge?.color),
            ),
          ),
          InkWell(
            onTap: () {
              if (fromNotification == true) {
                Navigator.of(context).pushAndRemoveUntil(MaterialPageRoute(builder: (BuildContext context) => const DashboardScreen()), (route) => false);
              } else {
                Navigator.of(context).pop();
              }
              Provider.of<OrderDetailsController>(context, listen: false).emptyOrderDetails();
            },
            child: const Padding(
              padding: EdgeInsets.symmetric(vertical: Dimensions.paddingSizeDefault, horizontal: 0),
              child: Icon(CupertinoIcons.back),
            ),
          ),
        ],
      );
    }

    return Stack(children: [
      Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              RichText(
                text: TextSpan(
                  text: '${getTranslated('order', context) ?? 'Order'} #',
                  style: robotoRegular.copyWith(
                    color: Theme.of(context).textTheme.bodyLarge?.color,
                    fontSize: Dimensions.fontSizeDefault,
                  ),
                  children: [
                    TextSpan(
                      text: orderModel?.id.toString() ?? '',
                      style: robotoBold.copyWith(
                        color: Theme.of(context).primaryColor,
                        fontSize: Dimensions.fontSizeLarge,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: Dimensions.paddingSizeExtraSmall),

              RichText(
                text: TextSpan(
                  text: '${getTranslated('your_order_is', context) ?? 'Status:'} ',
                  style: titilliumRegular.copyWith(
                    fontSize: Dimensions.fontSizeDefault,
                    color: Theme.of(context).hintColor,
                  ),
                  children: [
                    TextSpan(
                      text: getTranslated('${orderModel?.orderStatus}', context) ?? orderModel?.orderStatus?.replaceAll('_', ' ').capitalize() ?? '',
                      style: robotoBold.copyWith(
                        fontSize: Dimensions.fontSizeDefault,
                        color: orderModel?.orderStatus == 'delivered'
                          ? Colors.green
                          : orderModel?.orderStatus == 'pending'
                          ? Theme.of(context).primaryColor
                          : orderModel?.orderStatus == 'confirmed'
                          ? Colors.teal
                          : orderModel?.orderStatus == 'processing'
                          ? Colors.orange
                          : ((orderModel?.orderStatus == 'canceled' || orderModel?.orderStatus == "failed")
                          ? Theme.of(context).colorScheme.error
                          : Theme.of(context).colorScheme.secondary
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),

      InkWell(
        onTap: () {
        if(fromNotification == true) {
          Navigator.of(context).pushAndRemoveUntil(MaterialPageRoute(builder: (BuildContext context) => const DashboardScreen()), (route)=> false);
        }else{
          Navigator.of(context).pop();
        }
          Provider.of<OrderDetailsController>(context, listen: false).emptyOrderDetails();
        },
        child: const Padding(
          padding: EdgeInsets.symmetric(vertical : Dimensions.paddingSizeDefault, horizontal: 0),
          child: Icon(CupertinoIcons.back),
        ),
      ),

      Positioned(
        right: 0,
        child: Consumer<OrderDetailsController>(
          builder: (context, orderProvider, _) {
            final String orderStatus = orderModel?.orderStatus?.toLowerCase() ?? '';
            bool canEdit = (Provider.of<SplashController>(Get.context!, listen: false).configModel?.canVendorEditOrder == 1
              && (orderStatus == 'pending' || orderStatus == 'confirmed'));

            bool onlyDigitalProduct = (orderProvider.orderDetails != null && orderProvider.orderDetails?.length == 1 && orderProvider.orderDetails?[0].productDetails?.productType  == 'digital') ;

            bool isPaymentVerified = (orderModel?.paymentStatus != 'paid' && orderModel?.paymentMethod == 'offline_payment');

            return Row(
              children: [
                InkWell(
                  onTap: () {
                    if(isPaymentVerified) {
                      showCustomSnackBarWidget(getTranslated('please_confirm_offline_payment', context) ?? '', context, sanckBarType: SnackBarType.warning);
                    } else if(onlyDigitalProduct) {
                      showCustomSnackBarWidget(getTranslated('order_containing_only_digital', context) ?? '', context, sanckBarType: SnackBarType.warning);
                    } else if(canEdit) {
                      showAnimatedDialogWidget(
                        context,
                        CustomConfirmationDialogWidget(
                          icon: Images.editOrderWarningIcon,
                          title: getTranslated('edit_this_order', context) ?? '',
                          description: getTranslated('make_sure_you_have_saved_all_changes', context) ?? '',
                          onYesPressed: () {
                            Navigator.of(context).pop();
                            Navigator.push(context, MaterialPageRoute(builder: (_) => EditProductScreen(orderDetails: orderProvider.orderDetails ?? [])));
                          },
                        )
                      );
                    } else {
                      showCustomSnackBarWidget(getTranslated('vendors_are_not_allowed_to_edit', context) ?? '', context, sanckBarType: SnackBarType.warning);
                    }
                  },
                  child: CustomAssetImageWidget(Images.editOrderIcon, height: 20, width: 20,
                    color: (canEdit && !onlyDigitalProduct && !isPaymentVerified) ? Theme.of(context).primaryColor : Theme.of(context).primaryColor.withValues(alpha: 0.4)
                  )
                ),

                InkWell(
                  onTap: () {
                    if (orderModel?.id != null) {
                      orderProvider.getOrderInvoice(orderModel!.id.toString(), context);
                    }
                  },
                  child: Container(
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
                    ),
                    child: orderProvider.isInvoiceLoading
                        ? Padding(
                        padding: EdgeInsets.all(Dimensions.paddingSizeSmall),
                        child: const SizedBox(height: 30, width: 30, child: CircularProgressIndicator(strokeWidth: 1)
                        )) : const Padding(
                      padding: EdgeInsets.all(Dimensions.paddingSizeSmall),
                      child: CustomAssetImageWidget(Images.downloadInvoice, height: 30, width: 30),
                    ),
                  ),
                )
              ],
            );
          },
        ),
      ),



    ]);
  }
}
