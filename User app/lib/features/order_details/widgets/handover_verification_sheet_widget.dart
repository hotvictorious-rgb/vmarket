import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_button_widget.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/show_custom_snakbar_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/controllers/order_details_controller.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

/// [AI] Pure Receiver-Driven Handover Verification Bottom Sheet for Customer Mobile App
/// Customer snaps photo proof first, then enters secret delivery/pickup code shown by rider or merchant.
class HandoverVerificationSheetWidget extends StatefulWidget {
  final int orderId;
  final bool isPickup;
  const HandoverVerificationSheetWidget({
    super.key,
    required this.orderId,
    this.isPickup = false,
  });

  @override
  State<HandoverVerificationSheetWidget> createState() => _HandoverVerificationSheetWidgetState();
}

class _HandoverVerificationSheetWidgetState extends State<HandoverVerificationSheetWidget> {
  final TextEditingController _codeController = TextEditingController();
  File? _capturedImage;
  final ImagePicker _picker = ImagePicker();

  Future<void> _takePhoto() async {
    try {
      final XFile? photo = await _picker.pickImage(
        source: ImageSource.camera,
        maxWidth: 1000,
        maxHeight: 1000,
        imageQuality: 75,
      );
      if (photo != null) {
        setState(() {
          _capturedImage = File(photo.path);
        });
      }
    } catch (e) {
      if (mounted) {
        showCustomSnackBar('Could not open camera: $e', context);
      }
    }
  }

  void _submit() {
    final code = _codeController.text.trim();
    if (_capturedImage == null) {
      showCustomSnackBar('Please snap a photo of the received parcel first', context);
      return;
    }
    if (code.length != 6) {
      showCustomSnackBar('Please enter the 6-digit secret code', context);
      return;
    }

    final controller = Provider.of<OrderDetailsController>(context, listen: false);

    if (widget.isPickup) {
      controller.confirmInShopPickup(
        orderId: widget.orderId,
        pickupCode: code,
        verificationImage: _capturedImage!,
        callback: (isSuccess, message) {
          if (isSuccess) {
            Navigator.of(context).pop();
            showCustomSnackBar(message, context, isError: false);
          } else {
            showCustomSnackBar(message, context);
          }
        },
      );
    } else {
      controller.confirmDoorstepDelivery(
        orderId: widget.orderId,
        deliveryCode: code,
        verificationImage: _capturedImage!,
        callback: (isSuccess, message) {
          if (isSuccess) {
            Navigator.of(context).pop();
            showCustomSnackBar(message, context, isError: false);
          } else {
            showCustomSnackBar(message, context);
          }
        },
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final title = widget.isPickup ? 'Confirm In-Store Pickup' : 'Confirm Doorstep Delivery';
    final subtitle = widget.isPickup
        ? 'Snap a photo of the parcel on the merchant counter and enter the 6-digit code shown by the merchant.'
        : 'Snap a photo of the received parcel and enter the 6-digit delivery code shown on the rider\'s phone.';

    return Container(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom + Dimensions.paddingSizeDefault,
        left: Dimensions.paddingSizeDefault,
        right: Dimensions.paddingSizeDefault,
        top: Dimensions.paddingSizeDefault,
      ),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Consumer<OrderDetailsController>(
        builder: (context, controller, _) {
          return SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Top Grabber Handle
                Container(
                  height: 4,
                  width: 40,
                  decoration: BoxDecoration(
                    color: Theme.of(context).hintColor.withValues(alpha: 0.3),
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
                const SizedBox(height: Dimensions.paddingSizeSmall),

                // Shield / Verification Header
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.verified_rounded, color: Theme.of(context).primaryColor, size: 22),
                    const SizedBox(width: 8),
                    Text(
                      title,
                      style: robotoBold.copyWith(fontSize: Dimensions.fontSizeLarge),
                    ),
                  ],
                ),
                const SizedBox(height: Dimensions.paddingSizeSmall),

                Text(
                  subtitle,
                  style: textRegular.copyWith(
                    color: Theme.of(context).hintColor,
                    fontSize: Dimensions.fontSizeSmall,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: Dimensions.paddingSizeLarge),

                // Step 1: Camera Photo Capture Box
                InkWell(
                  onTap: _takePhoto,
                  child: Container(
                    height: 140,
                    width: double.infinity,
                    decoration: BoxDecoration(
                      color: Theme.of(context).primaryColor.withValues(alpha: 0.04),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(
                        color: _capturedImage != null
                            ? Theme.of(context).primaryColor
                            : Theme.of(context).primaryColor.withValues(alpha: 0.3),
                        width: _capturedImage != null ? 2 : 1,
                      ),
                    ),
                    child: _capturedImage != null
                        ? Stack(
                            fit: StackFit.expand,
                            children: [
                              ClipRRect(
                                borderRadius: BorderRadius.circular(10),
                                child: Image.file(_capturedImage!, fit: BoxFit.cover),
                              ),
                              Positioned(
                                top: 8,
                                right: 8,
                                child: Container(
                                  padding: const EdgeInsets.all(4),
                                  decoration: const BoxDecoration(
                                    color: Colors.black54,
                                    shape: BoxShape.circle,
                                  ),
                                  child: const Icon(Icons.camera_alt_rounded, color: Colors.white, size: 18),
                                ),
                              ),
                            ],
                          )
                        : Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(Icons.add_a_photo_rounded, size: 38, color: Theme.of(context).primaryColor),
                              const SizedBox(height: 8),
                              Text(
                                'Step 1: Snap Photo Proof',
                                style: robotoBold.copyWith(
                                  color: Theme.of(context).primaryColor,
                                  fontSize: Dimensions.fontSizeDefault,
                                ),
                              ),
                              Text(
                                'Tap to open camera',
                                style: textRegular.copyWith(
                                  color: Theme.of(context).hintColor,
                                  fontSize: Dimensions.fontSizeExtraSmall,
                                ),
                              ),
                            ],
                          ),
                  ),
                ),
                const SizedBox(height: Dimensions.paddingSizeDefault),

                // Step 2: 6-Digit PIN Field
                Text(
                  'Step 2: Enter 6-Digit Secret Code',
                  style: textMedium.copyWith(fontSize: Dimensions.fontSizeSmall),
                ),
                const SizedBox(height: 6),
                TextField(
                  controller: _codeController,
                  keyboardType: TextInputType.number,
                  maxLength: 6,
                  textAlign: TextAlign.center,
                  style: robotoBold.copyWith(
                    fontSize: 24,
                    letterSpacing: 10,
                    color: Theme.of(context).primaryColor,
                  ),
                  decoration: InputDecoration(
                    counterText: '',
                    hintText: '0 0 0 0 0 0',
                    hintStyle: robotoBold.copyWith(
                      fontSize: 24,
                      letterSpacing: 10,
                      color: Theme.of(context).hintColor.withValues(alpha: 0.3),
                    ),
                    filled: true,
                    fillColor: Theme.of(context).primaryColor.withValues(alpha: 0.05),
                    contentPadding: const EdgeInsets.symmetric(vertical: 12),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(color: Theme.of(context).primaryColor.withValues(alpha: 0.4)),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(color: Theme.of(context).primaryColor, width: 2),
                    ),
                  ),
                ),
                const SizedBox(height: Dimensions.paddingSizeLarge),

                // Submit Button
                controller.isHandoverSubmitting
                    ? const Center(child: CircularProgressIndicator())
                    : CustomButton(
                        buttonText: widget.isPickup
                            ? 'Confirm Pickup & Earn Cashback'
                            : 'Confirm Delivery Receipt',
                        onTap: _submit,
                      ),
                const SizedBox(height: Dimensions.paddingSizeSmall),
              ],
            ),
          );
        },
      ),
    );
  }
}
