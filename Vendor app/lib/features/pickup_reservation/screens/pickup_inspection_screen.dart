import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_app_bar_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_button_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/textfeild/custom_text_feild_widget.dart';
import 'package:sixvalley_vendor_app/features/pickup_reservation/controllers/pickup_reservation_controller.dart';
import 'package:sixvalley_vendor_app/utill/dimensions.dart';
import 'package:sixvalley_vendor_app/utill/styles.dart';

class PickupInspectionScreen extends StatefulWidget {
  const PickupInspectionScreen({super.key});

  @override
  State<PickupInspectionScreen> createState() => _PickupInspectionScreenState();
}

class _PickupInspectionScreenState extends State<PickupInspectionScreen> {
  final TextEditingController _codeController = TextEditingController();
  final TextEditingController _notesController = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<PickupReservationController>(context, listen: false).reset();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const CustomAppBarWidget(title: 'Pickup Inspection'),
      body: Consumer<PickupReservationController>(
        builder: (context, controller, _) {
          return Padding(
            padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                if (controller.verifiedData == null) ...[
                  Text(
                    'Enter the customer\'s reservation code to verify and inspect their items.',
                    style: robotoRegular.copyWith(fontSize: Dimensions.fontSizeDefault),
                  ),
                  const SizedBox(height: Dimensions.paddingSizeLarge),
                  CustomTextFieldWidget(
                    textInputType: TextInputType.text,
                    controller: _codeController,
                    hintText: 'e.g. RES-XXXXXXXX',
                    border: true,
                  ),
                  const SizedBox(height: Dimensions.paddingSizeLarge),
                  controller.isLoading
                      ? const Center(child: CircularProgressIndicator())
                      : CustomButtonWidget(
                          btnTxt: 'Verify Code',
                          onTap: () {
                            if (_codeController.text.trim().isNotEmpty) {
                              controller.verifyReservationCode(_codeController.text, context);
                            }
                          },
                        ),
                ] else ...[
                  Expanded(
                    child: SingleChildScrollView(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Reservation Code: ${controller.verifiedData!['reservation_code']}', style: robotoBold.copyWith(fontSize: Dimensions.fontSizeLarge)),
                          const SizedBox(height: Dimensions.paddingSizeSmall),
                          Text('Status: ${controller.verifiedData!['current_status']}', style: robotoRegular),
                          const SizedBox(height: Dimensions.paddingSizeSmall),
                          Text(
                            'Customer: ${controller.verifiedData!['customer_summary']?['name']} (${controller.verifiedData!['customer_summary']?['phone_masked']})',
                            style: robotoRegular,
                          ),
                          const SizedBox(height: Dimensions.paddingSizeLarge),
                          Text('Items to Inspect:', style: robotoBold.copyWith(fontSize: Dimensions.fontSizeDefault)),
                          const SizedBox(height: Dimensions.paddingSizeSmall),
                          if (controller.verifiedData!['items'] != null)
                            ...(controller.verifiedData!['items'] as List).map((item) {
                              return Card(
                                child: ListTile(
                                  title: Text(item['product_name'] ?? 'Unknown product'),
                                  subtitle: Text('Qty: ${item['quantity']} | Variant: ${item['variant'] ?? 'N/A'}'),
                                  trailing: Text('${item['line_total'] ?? 0} ${controller.verifiedData!['currency'] ?? ''}'),
                                ),
                              );
                            }),
                          const SizedBox(height: Dimensions.paddingSizeLarge),
                          CustomTextFieldWidget(
                            textInputType: TextInputType.text,
                            controller: _notesController,
                            hintText: 'Notes (Optional)',
                            border: true,
                          ),
                          const SizedBox(height: Dimensions.paddingSizeLarge),
                        ],
                      ),
                    ),
                  ),
                  if (controller.isLoading)
                    const Center(child: CircularProgressIndicator())
                  else
                    Row(
                      children: [
                        Expanded(
                          child: CustomButtonWidget(
                            btnTxt: 'Reject',
                            backgroundColor: Colors.red,
                            onTap: () {
                              controller.rejectInspection(
                                controller.verifiedData!['reservation_code'],
                                _notesController.text,
                                context,
                              );
                            },
                          ),
                        ),
                        const SizedBox(width: Dimensions.paddingSizeSmall),
                        Expanded(
                          child: CustomButtonWidget(
                            btnTxt: 'Accept',
                            backgroundColor: Colors.green,
                            onTap: () {
                              controller.acceptInspection(
                                controller.verifiedData!['reservation_code'],
                                _notesController.text,
                                context,
                              );
                            },
                          ),
                        ),
                      ],
                    ),
                ],
              ],
            ),
          );
        },
      ),
    );
  }
}
