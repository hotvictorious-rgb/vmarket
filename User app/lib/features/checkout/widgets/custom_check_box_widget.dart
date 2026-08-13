import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/controllers/checkout_controller.dart';
import 'package:flutter_sixvalley_ecommerce/theme/controllers/theme_controller.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_image_widget.dart';
import 'package:provider/provider.dart';

class CustomCheckBoxWidget extends StatelessWidget {
  final int index;
  final bool isDigital;
  final String? icon;
  final String name;
  final String title;
  final double? padding;
  const CustomCheckBoxWidget({super.key,  required this.index, this.isDigital =  false, this.icon, required this.name, required this.title, this.padding});

  @override
  Widget build(BuildContext context) {
    return Consumer<CheckoutController>(
      builder: (context, order, child) {
        bool isSelected = order.paymentMethodIndex == index;
        return InkWell(
          onTap: () => order.setDigitalPaymentMethodName(index, name),
          child: Container(
            margin: const EdgeInsets.symmetric(vertical: 4),
            padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault, vertical: Dimensions.paddingSizeSmall),
            decoration: BoxDecoration(
              color: isSelected ? const Color(0xFF6A1B9A).withValues(alpha: 0.05) : Theme.of(context).cardColor,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(
                color: isSelected ? const Color(0xFF6A1B9A) : Theme.of(context).primaryColor.withValues(alpha: 0.10),
                width: isSelected ? 1.5 : 1,
              ),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.03),
                  blurRadius: 6,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.start,
              children: [
                Container(
                  height: 38, width: 50,
                  padding: const EdgeInsets.all(4),
                  decoration: BoxDecoration(
                    color: Theme.of(context).cardColor,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: Colors.black.withValues(alpha: 0.06)),
                  ),
                  child: CustomImageWidget(image: icon!, fit: BoxFit.contain),
                ),
                const SizedBox(width: Dimensions.paddingSizeDefault),

                Expanded(
                  child: Text(
                    title,
                    style: textBold.copyWith(
                      fontSize: Dimensions.fontSizeDefault,
                      color: isSelected ? const Color(0xFF4A148C) : Theme.of(context).textTheme.bodyLarge?.color,
                    ),
                  ),
                ),

                Container(
                  height: 20, width: 20,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: isSelected ? const Color(0xFF6A1B9A) : Colors.transparent,
                    border: Border.all(
                      color: isSelected ? const Color(0xFF6A1B9A) : Theme.of(context).hintColor.withValues(alpha: 0.4),
                      width: 2,
                    ),
                  ),
                  child: isSelected ? const Icon(Icons.check, color: Colors.white, size: 13) : null,
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
