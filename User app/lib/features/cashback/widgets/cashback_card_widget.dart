import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/features/cashback/controllers/cashback_controller.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:provider/provider.dart';

class CashbackCardWidget extends StatelessWidget {
  const CashbackCardWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return Consumer<CashbackController>(
      builder: (context, cashbackController, _) {
        final summary = cashbackController.cashbackSummary;
        final available = summary?.availableCashbackAmount ?? '0.00';
        final pending = summary?.pendingCashbackAmount ?? '0.00';

        return Container(
          width: double.infinity,
          padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFF4A148C), Color(0xFF6A1B9A)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: const Color(0xFFFFD700).withValues(alpha: 0.35), width: 1.2),
            boxShadow: [
              BoxShadow(
                color: const Color(0xFF4A148C).withValues(alpha: 0.25),
                blurRadius: 12,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      const Icon(Icons.stars_rounded, color: Color(0xFFFFD700), size: 22),
                      const SizedBox(width: 8),
                      Text(
                        'Victorious Rewards (5% Cashback)',
                        style: textBold.copyWith(color: Colors.white, fontSize: Dimensions.fontSizeLarge),
                      ),
                    ],
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFFD700).withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(100),
                      border: Border.all(color: const Color(0xFFFFD700), width: 0.8),
                    ),
                    child: Text(
                      'NGN',
                      style: textBold.copyWith(color: const Color(0xFFFFD700), fontSize: Dimensions.fontSizeExtraSmall),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: Dimensions.paddingSizeSmall),

              // Available Balance
              Text(
                'Available Cashback',
                style: textRegular.copyWith(color: Colors.white70, fontSize: Dimensions.fontSizeSmall),
              ),
              const SizedBox(height: 4),
              Text(
                '₦$available',
                style: textBold.copyWith(color: const Color(0xFFFFD700), fontSize: Dimensions.fontSizeOverLarge + 4),
              ),
              const SizedBox(height: Dimensions.paddingSizeSmall),

              const Divider(color: Colors.white24, height: 1),
              const SizedBox(height: Dimensions.paddingSizeSmall),

              // Pending Balance (held during 24h refund inspection window)
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      const Icon(Icons.hourglass_top_rounded, color: Colors.amberAccent, size: 16),
                      const SizedBox(width: 6),
                      Text(
                        'Pending (24h Inspection Hold):',
                        style: textRegular.copyWith(color: Colors.white70, fontSize: Dimensions.fontSizeSmall),
                      ),
                    ],
                  ),
                  Text(
                    '₦$pending',
                    style: textBold.copyWith(color: Colors.amberAccent, fontSize: Dimensions.fontSizeDefault),
                  ),
                ],
              ),
              const SizedBox(height: Dimensions.paddingSizeSmall),

              // Policy notice
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.black26,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  '💡 5% cashback reward earned on delivered merchandise, available after the 24-hour customer inspection window. Non-withdrawable account credit.',
                  style: textRegular.copyWith(color: Colors.white70, fontSize: 11),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}