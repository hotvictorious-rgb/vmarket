
import 'package:flutter/material.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/models/order_model.dart';
import 'package:sixvalley_delivery_boy/features/order_details/widgets/round_border_icon_widget.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:url_launcher/url_launcher.dart';

class CallAndChatWidget extends StatelessWidget {
  final OrderModel? orderModel;
  final bool isSeller;
  final bool isAdmin;
  const CallAndChatWidget({Key? key, this.orderModel, this.isSeller = false, this.isAdmin = false}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    String? phone = isSeller? orderModel!.sellerInfo!.phone :orderModel!.isGuest! ? orderModel?.shippingAddress?.phone: orderModel!.customer?.phone??'';
    String? name = '';
    if(isAdmin){
      name = 'admin';
    }else{
      name = isSeller ? orderModel!.sellerInfo!.shop!.name! : '${orderModel!.customer?.fName??''} ${orderModel!.customer?.lName??''}';
    }

    return Row(children: [

      InkWell(onTap: () => _launchUrl("tel:$phone"), child: const RoundBorderIconWidget(image: Images.callIcon)),

      // [AI] 1-Click WhatsApp Coordination for Active Delivery
      if (phone != null && phone.isNotEmpty)
        Padding(
          padding: const EdgeInsets.only(left: 8.0),
          child: InkWell(
            onTap: () {
              final cleanPhone = phone.replaceAll(RegExp(r'[^0-9]'), '');
              final nigerianPhone = cleanPhone.startsWith('0') ? '234${cleanPhone.substring(1)}' : cleanPhone.startsWith('234') ? cleanPhone : '234$cleanPhone';
              final greeting = Uri.encodeComponent(
                'Hello ${name ?? "Customer"}! This is your Victorious MARKET dispatch rider. I have your order #${orderModel?.id ?? ""} and I am on the way.'
              );
              _launchUrl("https://wa.me/$nigerianPhone?text=$greeting");
            },
            child: Container(
              height: 38,
              width: 38,
              decoration: BoxDecoration(
                color: const Color(0xFF25D366),
                shape: BoxShape.circle,
                boxShadow: [
                  BoxShadow(color: Colors.black.withValues(alpha: 0.1), blurRadius: 4, offset: const Offset(0, 2)),
                ],
              ),
              child: const Icon(Icons.chat, color: Colors.white, size: 18),
            ),
          ),
        ),
    ]);
  }
}

Future<void> _launchUrl(String _url) async {
  if (!await launchUrl(Uri.parse(_url))) {
    throw 'Could not launch $_url';
  }
}