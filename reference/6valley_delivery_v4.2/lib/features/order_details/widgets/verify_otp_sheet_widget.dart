
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:pin_code_fields/pin_code_fields.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/models/order_model.dart';
import 'package:sixvalley_delivery_boy/features/order_details/controllers/order_details_controller.dart';
import 'package:sixvalley_delivery_boy/features/order_details/screens/order_delivered_screen.dart';
import 'package:sixvalley_delivery_boy/helper/price_converter.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_button_widget.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_snackbar_widget.dart';


class VerifyDeliverySheetWidget extends StatefulWidget {
  final OrderModel? orderModel;
  final double? totalPrice;
  const VerifyDeliverySheetWidget({Key? key, this.orderModel, this.totalPrice}) : super(key: key);
  @override
  State<VerifyDeliverySheetWidget> createState() => _VerifyDeliverySheetWidgetState();
}

class _VerifyDeliverySheetWidgetState extends State<VerifyDeliverySheetWidget> {
 String otp = '';
 bool invalidOtp = false;
  @override
  Widget build(BuildContext context) {
    return Container(decoration: BoxDecoration(color: Theme.of(context).canvasColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20))),
      child: GetBuilder<OrderDetailsController>(builder: (orderController) {
        return Padding(padding:  EdgeInsets.all(Dimensions.paddingSizeLarge),
          child: Column(mainAxisSize: MainAxisSize.min, children: [

            Container(height: 5, width: 50,
              decoration: BoxDecoration(borderRadius: BorderRadius.circular(Dimensions.radiusLarge),
                color: Theme.of(context).disabledColor.withValues(alpha:0.5))),


            orderController.otpVerified ?
                Padding(padding:  EdgeInsets.only(bottom: Dimensions.paddingSizeOverLarge, top: 50),
                  child: Column(children: [
                    Text('collect_money_from_customer'.tr, style: rubikBold, textAlign: TextAlign.center),
                    SizedBox(height: Dimensions.paddingSizeLarge),

                    Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                        Text('${'order_amount'.tr}:  ', style: rubikRegular.copyWith(), textAlign: TextAlign.center),
                        Text(PriceConverter.convertPrice(widget.totalPrice),
                            style: rubikBold.copyWith(color: Get.isDarkMode ? Theme.of(context).primaryColorLight : Theme.of(context).primaryColor,
                                fontSize: Dimensions.fontSizeLarge), textAlign: TextAlign.center)]),
                    SizedBox(height: Dimensions.paddingSizeLarge)])) :

            Column(children: [
               SizedBox(height: Dimensions.paddingSizeLarge),

              Text('otp_verification'.tr, style: rubikBold, textAlign: TextAlign.center),
               SizedBox(height: Dimensions.paddingSizeLarge),

              Text('enter_otp_number'.tr, style: rubikRegular.copyWith(
                  color: Theme.of(context).disabledColor), textAlign: TextAlign.center),
               SizedBox(height: Dimensions.paddingSizeLarge),

              SizedBox(width: 260,
                child: PinCodeTextField(
                  length: 6,
                  appContext: context,
                  keyboardType: TextInputType.number,
                  animationType: AnimationType.slide,
                  pinTheme: PinTheme(
                    shape: PinCodeFieldShape.underline,
                    fieldHeight: 30,
                    fieldWidth: 30,
                    borderWidth: 2,
                    borderRadius: BorderRadius.circular(Dimensions.radiusSmall),
                    selectedColor: invalidOtp? Theme.of(context).colorScheme.error : Theme.of(context).primaryColor,
                    selectedFillColor: Colors.white,
                    inactiveFillColor: Theme.of(context).cardColor,
                    inactiveColor: Theme.of(context).primaryColor.withValues(alpha:0.2),
                    activeColor: invalidOtp? Theme.of(context).colorScheme.error : Theme.of(context).primaryColor.withValues(alpha:0.7),
                    errorBorderColor: Theme.of(context).colorScheme.error,
                    activeFillColor: Theme.of(context).cardColor),
                  animationDuration: const Duration(milliseconds: 300),
                  backgroundColor: Colors.transparent,
                  enableActiveFill: true,
                  onChanged: (String text) {
                    setState(() {
                      otp = text;
                      if(otp.length<6){
                        invalidOtp = false;
                      }
                    });
                  },
                  beforeTextPaste: (text) => true)),
               SizedBox(height: Dimensions.paddingSizeSmall),

              invalidOtp?
              Text('wrong_otp'.tr, style: rubikRegular.copyWith(
                  color: Theme.of(context).colorScheme.error), textAlign: TextAlign.center):
              Text('collect_otp_from_customer'.tr, style: rubikRegular, textAlign: TextAlign.center),
               SizedBox(height: Dimensions.paddingSizeLarge)]),

            orderController.isLoading ? const Center(child: CircularProgressIndicator()) :
            CustomButtonWidget(
              btnTxt: orderController.otpVerified? 'ok'.tr : 'submit'.tr,
              onTap: () async {
              if(orderController.otpVerified){
                orderController.updatePaymentStatus(orderId: widget.orderModel!.id, status: 'paid').then((value) {
                  if (value?.statusCode == 200) {
                    orderController.updateOrderStatus(orderId: widget.orderModel!.id,
                        context: context, status: 'delivered').then((value) {
                      Navigator.of(context).pushReplacement(MaterialPageRoute(
                          builder: (_) => OrderDeliveredScreen(
                            orderID: widget.orderModel!.id.toString(), orderModel: widget.orderModel)));
                        });}});
              }else{
                if(otp.length == 6){
                 orderController.otpVerificationForOrderVerification(orderId: widget.orderModel!.id, otp: otp).then((value){
                   if(value?.statusCode == 200){
                     if(widget.orderModel?.paymentStatus != 'paid'){
                       orderController.toggleProceedToNext();
                     }else{
                       orderController.updateOrderStatus(orderId: widget.orderModel!.id,context: context,
                           status: 'delivered').then((value) {
                         Navigator.of(context).push(MaterialPageRoute(
                             builder: (_) => OrderDeliveredScreen(orderID: widget.orderModel!.id.toString(),
                               orderModel: widget.orderModel,)));
                       });}
                   }else{setState(() {invalidOtp = true;});}
                 });
                }else{showCustomSnackBarWidget('input_valid_otp'.tr);}}}
            ),

            if(!orderController.otpVerified)
            Padding(padding:  EdgeInsets.only(top: Dimensions.paddingSizeSmall),
              child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                Text('did_not_get_any_OTP'.tr,
                  style: rubikRegular.copyWith(color: Theme.of(context).hintColor, fontSize: Dimensions.fontSizeSmall),),


                InkWell(onTap: () => orderController.resendOtpForOrderVerification(orderId: widget.orderModel!.id),
                  child: Text('resend_it'.tr, style: rubikMedium.copyWith(
                      color: Theme.of(context).hintColor, fontSize: Dimensions.fontSizeSmall)))])),


            SizedBox(height: Dimensions.paddingSizeSmall),
          ]),
        );
      }),
    );
  }
}
