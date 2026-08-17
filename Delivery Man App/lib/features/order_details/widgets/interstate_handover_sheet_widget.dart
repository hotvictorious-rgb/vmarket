import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_button_widget.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_snackbar_widget.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/models/order_model.dart';
import 'package:sixvalley_delivery_boy/features/order_details/controllers/order_details_controller.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';

class InterstateHandoverSheetWidget extends StatefulWidget {
  final OrderModel? orderModel;
  const InterstateHandoverSheetWidget({Key? key, this.orderModel}) : super(key: key);

  @override
  State<InterstateHandoverSheetWidget> createState() => _InterstateHandoverSheetWidgetState();
}

class _InterstateHandoverSheetWidgetState extends State<InterstateHandoverSheetWidget> {
  final TextEditingController _driverPhoneController = TextEditingController();
  final TextEditingController _driverVehicleController = TextEditingController();
  final TextEditingController _waybillSlipController = TextEditingController();

  @override
  void dispose() {
    _driverPhoneController.dispose();
    _driverVehicleController.dispose();
    _waybillSlipController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).canvasColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: GetBuilder<OrderDetailsController>(
        builder: (orderController) {
          return Padding(
            padding: EdgeInsets.all(Dimensions.paddingSizeLarge),
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    height: 5,
                    width: 50,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(Dimensions.radiusLarge),
                      color: Theme.of(context).disabledColor.withValues(alpha: 0.5),
                    ),
                  ),
                  SizedBox(height: Dimensions.paddingSizeLarge),

                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.directions_bus_rounded, color: Color(0xFF2E7D32), size: 24),
                      const SizedBox(width: 8),
                      Text(
                        'Interstate Park Handover'.tr,
                        style: rubikBold.copyWith(fontSize: Dimensions.fontSizeLarge),
                        textAlign: TextAlign.center,
                      ),
                    ],
                  ),
                  SizedBox(height: Dimensions.paddingSizeSmall),

                  Text(
                    'Enter the bus driver details at the motor park. A Driver Transit Code will be generated for the recipient to verify release.'
                        .tr,
                    style: rubikRegular.copyWith(color: Theme.of(context).disabledColor, fontSize: Dimensions.fontSizeSmall),
                    textAlign: TextAlign.center,
                  ),
                  SizedBox(height: Dimensions.paddingSizeLarge),

                  // Driver Phone
                  Container(
                    decoration: BoxDecoration(
                      border: Border.all(color: Theme.of(context).primaryColor.withValues(alpha: 0.3)),
                      borderRadius: BorderRadius.circular(Dimensions.radiusSmall),
                    ),
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    child: TextField(
                      controller: _driverPhoneController,
                      keyboardType: TextInputType.phone,
                      decoration: InputDecoration(
                        icon: const Icon(Icons.phone_in_talk, size: 20),
                        hintText: 'Bus Driver Phone (e.g. 08012345678)'.tr,
                        hintStyle: rubikRegular.copyWith(fontSize: Dimensions.fontSizeSmall, color: Colors.grey),
                        border: InputBorder.none,
                      ),
                    ),
                  ),
                  SizedBox(height: Dimensions.paddingSizeDefault),

                  // Vehicle No
                  Container(
                    decoration: BoxDecoration(
                      border: Border.all(color: Theme.of(context).primaryColor.withValues(alpha: 0.3)),
                      borderRadius: BorderRadius.circular(Dimensions.radiusSmall),
                    ),
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    child: TextField(
                      controller: _driverVehicleController,
                      decoration: InputDecoration(
                        icon: const Icon(Icons.directions_bus, size: 20),
                        hintText: 'Bus / Plate No (e.g. AKTC Bus 402)'.tr,
                        hintStyle: rubikRegular.copyWith(fontSize: Dimensions.fontSizeSmall, color: Colors.grey),
                        border: InputBorder.none,
                      ),
                    ),
                  ),
                  SizedBox(height: Dimensions.paddingSizeDefault),

                  // Waybill Slip
                  Container(
                    decoration: BoxDecoration(
                      border: Border.all(color: Theme.of(context).primaryColor.withValues(alpha: 0.3)),
                      borderRadius: BorderRadius.circular(Dimensions.radiusSmall),
                    ),
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    child: TextField(
                      controller: _waybillSlipController,
                      decoration: InputDecoration(
                        icon: const Icon(Icons.receipt_long, size: 20),
                        hintText: 'Waybill Slip / Receipt No (Optional)'.tr,
                        hintStyle: rubikRegular.copyWith(fontSize: Dimensions.fontSizeSmall, color: Colors.grey),
                        border: InputBorder.none,
                      ),
                    ),
                  ),
                  SizedBox(height: Dimensions.paddingSizeExtraLarge),

                  orderController.isLoading
                      ? const Center(child: CircularProgressIndicator())
                      : CustomButtonWidget(
                          btnTxt: 'Generate Driver Code & Handover'.tr,
                          onTap: () async {
                            String phone = _driverPhoneController.text.trim();
                            String vehicle = _driverVehicleController.text.trim();
                            String waybill = _waybillSlipController.text.trim();

                            if (phone.isEmpty) {
                              showCustomSnackBarWidget('Please enter driver phone number'.tr);
                              return;
                            }
                            if (vehicle.isEmpty) {
                              showCustomSnackBarWidget('Please enter bus / vehicle plate number'.tr);
                              return;
                            }

                            String? code = await orderController.interstateDriverHandover(
                              orderId: widget.orderModel!.id,
                              driverPhone: phone,
                              driverVehicleNo: vehicle,
                              waybillSlipNo: waybill,
                              context: context,
                            );

                            if (code != null) {
                              Get.back(); // close sheet
                              _showTransitCodeDialog(context, code);
                            }
                          },
                        ),
                  SizedBox(height: Dimensions.paddingSizeSmall),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  void _showTransitCodeDialog(BuildContext context, String transitCode) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: [
            const Icon(Icons.check_circle, color: Color(0xFF2E7D32), size: 26),
            const SizedBox(width: 8),
            Text('Handover Recorded'.tr, style: rubikBold.copyWith(fontSize: Dimensions.fontSizeLarge)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              'Read this Driver Release Code to the bus driver now:'.tr,
              style: rubikRegular.copyWith(fontSize: Dimensions.fontSizeDefault),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
              decoration: BoxDecoration(
                color: const Color(0xFFE8F5E9),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: const Color(0xFF4CAF50), width: 2),
              ),
              child: Text(
                transitCode,
                style: rubikBold.copyWith(fontSize: 28, color: const Color(0xFF1B5E20), letterSpacing: 4),
              ),
            ),
            const SizedBox(height: 12),
            Text(
              'The customer will enter this code on their Victorious Market app at the destination park to confirm parcel receipt.'
                  .tr,
              style: rubikRegular.copyWith(fontSize: Dimensions.fontSizeExtraSmall, color: Colors.grey[700]),
              textAlign: TextAlign.center,
            ),
          ],
        ),
        actions: [
          ElevatedButton(
            onPressed: () {
              Clipboard.setData(ClipboardData(text: transitCode));
              Navigator.of(ctx).pop();
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF2E7D32),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            child: Text('Copy Code & Done'.tr, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }
}
