import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';

class CheckoutStepsProgressWidget extends StatelessWidget {
  final int currentStep; // 1 = Cart, 2 = Shipping & Inspection, 3 = Payment

  const CheckoutStepsProgressWidget({super.key, required this.currentStep});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.symmetric(
        horizontal: Dimensions.paddingSizeDefault,
        vertical: Dimensions.paddingSizeSmall,
      ),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(
          color: const Color(0xFF5E17EB).withValues(alpha: 0.10),
          width: 1,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          _buildStep(
            context: context,
            stepIndex: 1,
            title: getTranslated('cart', context) ?? 'Cart',
            isCurrent: currentStep == 1,
            isDone: currentStep > 1,
          ),
          Icon(
            Icons.chevron_right_rounded,
            size: 16,
            color: Theme.of(context).hintColor.withValues(alpha: 0.4),
          ),
          _buildStep(
            context: context,
            stepIndex: 2,
            title: getTranslated('shipping', context) ?? 'Shipping',
            isCurrent: currentStep == 2,
            isDone: currentStep > 2,
          ),
          Icon(
            Icons.chevron_right_rounded,
            size: 16,
            color: Theme.of(context).hintColor.withValues(alpha: 0.4),
          ),
          _buildStep(
            context: context,
            stepIndex: 3,
            title: getTranslated('payment', context) ?? 'Payment',
            isCurrent: currentStep == 3,
            isDone: currentStep > 3,
          ),
        ],
      ),
    );
  }

  Widget _buildStep({
    required BuildContext context,
    required int stepIndex,
    required String title,
    required bool isCurrent,
    required bool isDone,
  }) {
    if (isCurrent) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          gradient: const LinearGradient(
            colors: [Color(0xFF5E17EB), Color(0xFF4A148C)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          borderRadius: BorderRadius.circular(999),
          boxShadow: [
            BoxShadow(
              color: const Color(0xFF5E17EB).withValues(alpha: 0.30),
              blurRadius: 6,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 18,
              height: 18,
              decoration: const BoxDecoration(
                color: Color(0xFFFFD700),
                shape: BoxShape.circle,
              ),
              child: Center(
                child: Text(
                  stepIndex.toString(),
                  style: textBold.copyWith(
                    fontSize: 10,
                    color: const Color(0xFF1B1035),
                  ),
                ),
              ),
            ),
            const SizedBox(width: 6),
            Text(
              title,
              style: textBold.copyWith(
                fontSize: Dimensions.fontSizeExtraSmall + 1,
                color: Colors.white,
              ),
            ),
          ],
        ),
      );
    }

    if (isDone) {
      return Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 18,
            height: 18,
            decoration: BoxDecoration(
              color: const Color(0xFF10B981).withValues(alpha: 0.12),
              shape: BoxShape.circle,
              border: Border.all(color: const Color(0xFF10B981), width: 1),
            ),
            child: const Center(
              child: Icon(Icons.check, size: 12, color: Color(0xFF10B981)),
            ),
          ),
          const SizedBox(width: 4),
          Text(
            title,
            style: textBold.copyWith(
              fontSize: Dimensions.fontSizeExtraSmall + 1,
              color: const Color(0xFF059669),
            ),
          ),
        ],
      );
    }

    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 18,
          height: 18,
          decoration: BoxDecoration(
            color: Theme.of(context).hintColor.withValues(alpha: 0.12),
            shape: BoxShape.circle,
          ),
          child: Center(
            child: Text(
              stepIndex.toString(),
              style: textRegular.copyWith(
                fontSize: 10,
                color: Theme.of(context).hintColor,
              ),
            ),
          ),
        ),
        const SizedBox(width: 4),
        Text(
          title,
          style: textRegular.copyWith(
            fontSize: Dimensions.fontSizeExtraSmall + 1,
            color: Theme.of(context).hintColor,
          ),
        ),
      ],
    );
  }
}