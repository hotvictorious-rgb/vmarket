import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/features/chat/controllers/chat_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/order/domain/models/order_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/controllers/order_details_controller.dart';
import 'package:flutter_sixvalley_ecommerce/helper/route_healper.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:flutter_sixvalley_ecommerce/utill/images.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';


class CallAndChatWidget extends StatelessWidget {
  final OrderDetailsController? orderProvider;
  final Orders? orderModel;
  final bool isSeller;
  const CallAndChatWidget({super.key, this.orderProvider, this.isSeller = false, this.orderModel});

  @override
  Widget build(BuildContext context) {
    String? phone = isSeller? orderProvider!.orderDetails![0].seller!.phone : orderModel!.deliveryMan!.phone;
    String? name = isSeller? orderProvider!.orderDetails![0].seller!.shop!.name : '${orderModel!.deliveryMan!.fName!} ${orderModel!.deliveryMan!.lName!}';
    int? id =  isSeller ? orderProvider!.orderDetails![0].seller!.id : orderModel!.deliveryMan!.id;
    String? image =  isSeller ? orderProvider!.orderDetails![0].seller!.imageFullUrl?.path : orderModel!.deliveryMan!.imageFullUrl?.path;

    return Row(
      children: [
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

        if (!isSeller)
          InkWell(
            onTap: () {
              Provider.of<ChatController>(context, listen: false).setUserTypeIndex(context, 0);
              RouterHelper.getChatScreenRoute(
                action: RouteAction.push,
                image: image,
                id: id,
                name: name ?? '',
                userType: 0,
                isShopTemporaryClosed: false,
                isShopOnVacation: false,
                orderId: orderModel?.id,
                orderStatus: orderModel?.orderStatus,
              );
            },
            child: Container(
              height: 38,
              padding: const EdgeInsets.symmetric(horizontal: 12),
              margin: const EdgeInsets.symmetric(horizontal: 4),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF6A1B9A), Color(0xFF4A148C)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(12),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF4A148C).withValues(alpha: 0.2),
                    blurRadius: 4,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Row(
                children: [
                  const Icon(Icons.chat_bubble_outline_rounded, color: Colors.white, size: 16),
                  const SizedBox(width: 4),
                  Text(
                    'Chat',
                    style: titilliumBold.copyWith(color: Colors.white, fontSize: Dimensions.fontSizeSmall),
                  ),
                ],
              ),
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