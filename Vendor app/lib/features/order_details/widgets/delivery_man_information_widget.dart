import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:sixvalley_vendor_app/features/chat/controllers/chat_controller.dart';
import 'package:sixvalley_vendor_app/features/chat/screens/chat_screen.dart';
import 'package:sixvalley_vendor_app/features/order/domain/models/order_model.dart';
import 'package:sixvalley_vendor_app/helper/color_helper.dart';
import 'package:sixvalley_vendor_app/localization/language_constrants.dart';
import 'package:sixvalley_vendor_app/utill/dimensions.dart';
import 'package:sixvalley_vendor_app/utill/images.dart';
import 'package:sixvalley_vendor_app/utill/styles.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_image_widget.dart';


class DeliveryManContactInformationWidget extends StatelessWidget {
  final String? orderType;
  final Order? orderModel;
  final bool? onlyDigital;
  const DeliveryManContactInformationWidget({super.key, this.orderModel, this.orderType, this.onlyDigital});

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        Container(
          decoration: BoxDecoration(
            color: Theme.of(context).cardColor,
            boxShadow: [BoxShadow(color: Theme.of(context).hintColor.withValues(alpha:0.2), spreadRadius:1.5, blurRadius: 3)],
          ),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const SizedBox(height: Dimensions.paddingSizeSmall),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
              child: Text(getTranslated('deliveryman_information', context)!,
                style: robotoBold.copyWith(fontSize: Dimensions.fontSizeLarge, color: Theme.of(context).textTheme.bodyLarge?.color)
              ),
            ),
            const SizedBox(height: Dimensions.paddingSizeSmall),

            Divider(thickness: 0.2, height: 1, color: Theme.of(context).hintColor.withValues(alpha: .65)),
            const SizedBox(height: Dimensions.paddingSizeSmall),

            Padding(
              padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
              child: Row(children: [ClipRRect(borderRadius: BorderRadius.circular(50),
                child: CustomImageWidget( height: 50,width: 50, fit: BoxFit.cover,
                image: '${orderModel!.deliveryMan!.imageFullUrl?.path}')
              ),
              const SizedBox(width: Dimensions.paddingSizeSmall),

                Expanded(child: Column( crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text('${orderModel!.deliveryMan!.fName ?? ''} ''${orderModel!.deliveryMan!.lName ?? ''}',
                      style: titilliumRegular.copyWith(color: ColorHelper.blendColors(Colors.white, Theme.of(context).textTheme.bodyLarge!.color!, 0.7),
                          fontSize: Dimensions.fontSizeDefault)),

                  const SizedBox(height: Dimensions.paddingSizeExtraSmall,),

                  Row(children: [
                    Image.asset(Images.phone, width: 15),
                    const SizedBox(width: Dimensions.paddingSizeSmall),
                    Text('${orderModel!.deliveryMan!.countryCode} ${orderModel!.deliveryMan!.phone}',
                        style: titilliumRegular.copyWith(color: ColorHelper.blendColors(Colors.white, Theme.of(context).textTheme.bodyLarge!.color!, 0.7),
                            fontSize: Dimensions.fontSizeDefault)),
                    ]
                  ),

                  const SizedBox(height: Dimensions.paddingSizeExtraSmall,),

                  Row(children: [
                    Image.asset(Images.email, width: 15),
                    const SizedBox(width: Dimensions.paddingSizeSmall),
                    Expanded(
                      child: Text(orderModel!.deliveryMan!.email ?? '',
                          style: titilliumRegular.copyWith(color: ColorHelper.blendColors(Colors.white, Theme.of(context).textTheme.bodyLarge!.color!, 0.7),
                              fontSize: Dimensions.fontSizeDefault),
                          overflow: TextOverflow.ellipsis),
                    ),
                    InkWell(
                      onTap: () {
                        Provider.of<ChatController>(context, listen: false).setUserTypeIndex(context, 1);
                        Navigator.of(context).push(MaterialPageRoute(builder: (_) => ChatScreen(
                          userId: orderModel!.deliveryMan!.id,
                          name: '${orderModel!.deliveryMan!.fName ?? ''} ${orderModel!.deliveryMan!.lName ?? ''}'.trim(),
                          orderId: orderModel!.id,
                          orderStatus: orderModel!.orderStatus,
                        )));
                      },
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            colors: [Color(0xFF6A1B9A), Color(0xFF4A148C)],
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                          ),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.chat_bubble_outline_rounded, color: Colors.white, size: 14),
                            const SizedBox(width: 4),
                            Text('Chat', style: robotoBold.copyWith(color: Colors.white, fontSize: 11)),
                          ],
                        ),
                      ),
                    ),
                    ],
                  ),


                ],))
                ],
              ),
            ),
            const SizedBox(height: Dimensions.paddingSizeDefault),



          ]),
        ),
      ],
    );
  }
}
