import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/features/order/domain/models/order_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/controllers/order_details_controller.dart';
import 'package:flutter_sixvalley_ecommerce/utill/images.dart';
import 'package:url_launcher/url_launcher.dart';

class CallAndChatWidget extends StatelessWidget {
  final OrderDetailsController? orderProvider;
  final Orders? orderModel;
  final bool isSeller;
  const CallAndChatWidget({super.key, this.orderProvider, this.isSeller = false, this.orderModel});

  @override
  Widget build(BuildContext context) {
    String? phone = isSeller? orderProvider!.orderDetails![0].seller!.phone : orderModel!.deliveryMan!.phone;

    return Row(
      children: [
        if (phone != null && phone.isNotEmpty)
          InkWell(
            onTap: () => _launchUrl("tel:$phone"),
            child: Container(
              width: 38,
              height: 38,
              margin: const EdgeInsets.symmetric(horizontal: 4),
              decoration: BoxDecoration(
                color: const Color(0xFF6A1B9A).withValues(alpha: 0.08),
                border: Border.all(color: const Color(0xFF6A1B9A).withValues(alpha: 0.2)),
                borderRadius: BorderRadius.circular(12),
              ),
              padding: const EdgeInsets.all(8),
              child: Image.asset(Images.callIcon, color: const Color(0xFF6A1B9A)),
            ),
          ),
      ],
    );
  }
}

Future<void> _launchUrl(String url) async {
  if (!await launchUrl(Uri.parse(url))) {
    throw 'Could not launch $url';
  }
}
