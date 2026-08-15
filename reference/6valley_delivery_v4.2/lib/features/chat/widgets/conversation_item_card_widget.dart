import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_ink_well_widget.dart';
import 'package:sixvalley_delivery_boy/features/chat/controllers/chat_controller.dart';
import 'package:sixvalley_delivery_boy/features/splash/controllers/splash_controller.dart';
import 'package:sixvalley_delivery_boy/features/chat/domain/models/chat_model.dart';
import 'package:sixvalley_delivery_boy/helper/string_extensions.dart';
import 'package:sixvalley_delivery_boy/utill/app_constants.dart';
import 'package:sixvalley_delivery_boy/features/chat/screens/chat_screen.dart';
import 'package:timeago/timeago.dart' as timeago;
import 'package:sixvalley_delivery_boy/helper/date_converter.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_image_widget.dart';

class ConversationItemCardWidget extends StatelessWidget {
  final Chat? chat;
  const ConversationItemCardWidget({Key? key, this.chat}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return GetBuilder<ChatController>(
      builder: (chatController) {
        String? image = chatController.userTypeIndex == 0?  chat!.sellerInfo != null ?
        chat!.sellerInfo!.shops![0].imageFullUrl?.path??'':'':
        chatController.userTypeIndex == 1? chat!.customer?.imageFullUrl?.path ?? '':
        Get.find<SplashController>().configModel!.companyFavIcon?.path ?? '';
        String name = chatController.userTypeIndex == 0?   chat!.sellerInfo != null ?
        chat!.sellerInfo!.shops![0].name! :'Shop not found' :
        chatController.userTypeIndex == 1?
        '${chat!.customer?.fName?? ''} ${chat!.customer?.lName??''}' : AppConstants.companyName;

        int? userId = chatController.userTypeIndex == 0?  chat!.sellerId :chatController.userTypeIndex == 1? chat!.userId : 0;

        String path = chatController.userTypeIndex == 3? image: image;

        return Container(margin:  EdgeInsets.symmetric(vertical: Dimensions.paddingSizeSmall),
          padding: EdgeInsets.all(Dimensions.paddingSizeMin),
          decoration: BoxDecoration(border: Border.all(color: Theme.of(context).primaryColor.withValues(alpha:.10), width: 1),
            color: (chat?.seenByDeliveryMan ?? false) ? Theme.of(context).cardColor : Theme.of(context).primaryColor.withValues(alpha: 0.05),
            borderRadius: BorderRadius.circular(Dimensions.chatProfileSize),
          ),
          child: CustomInkWellWidget(onTap: () => Get.to(ChatScreen(userId: userId, name: name, image: image,)),
            highlightColor: Theme.of(context).colorScheme.surface.withValues(alpha:0.1),
            radius: Dimensions.profileImageSize,

            child: Stack(children: [
              Padding(padding:  EdgeInsets.all(Dimensions.paddingSizeExtraSmall),
                child: Row(children: [
                   Container(
                       decoration: BoxDecoration(
                         border: Border.all(
                           color: Theme.of(context).primaryColor.withValues(alpha:.15),
                           width: 1.2, // Border width
                         ),
                         borderRadius: BorderRadius.circular(100), // Optional: Rounded corners
                       ),
                       child: ClipOval(child: CustomImageWidget(height: 45, width: 45,fit: BoxFit.cover, image: path),)),
                   SizedBox(width: Dimensions.paddingSizeSmall),

                  Expanded(child: Column(mainAxisAlignment: MainAxisAlignment.start, crossAxisAlignment: CrossAxisAlignment.start, children: [
                     Row(
                       mainAxisAlignment: MainAxisAlignment.spaceBetween,
                       children: [
                         Expanded(
                           child: Text(name, style: rubikBold.copyWith(
                               color: chatController.userTypeIndex == 0 && chat!.sellerInfo == null ?
                               Colors.red : chatController.userTypeIndex == 1 && chat!.customer == null ?
                               Colors.red : Get.isDarkMode ? Theme.of(context).textTheme.bodyLarge?.color : Theme.of(context).primaryColor), overflow: TextOverflow.ellipsis,),
                         ),

                         SizedBox(width: Dimensions.paddingSizeExtraSmall),
                         Transform.translate(
                           offset: const Offset(0,-5),
                           child: Text(timeago.format(DateConverter.isoStringToLocalDate(chat!.createdAt!)).toCapitalized(),
                               style: rubikMedium.copyWith(
                                   color: Get.isDarkMode ? Theme.of(context).textTheme.bodyLarge?.color : Theme.of(context).primaryColor,
                                   fontSize: Dimensions.fontSizeExtraSmall
                               ),
                             overflow: TextOverflow.ellipsis,
                           ),
                         ),
                         SizedBox(width: Dimensions.paddingSizeExtraSmall),
                       ],
                     ),

                    Padding(
                      padding: EdgeInsets.only(top: Dimensions.paddingSizeExtraSmall),
                      child: Row(
                        children: [
                          Expanded(child: Text((chat!.message == null &&  chat!.attachment!.isNotEmpty) ? 'sent_attachment'.tr : chat!.message ?? "",
                              maxLines: 1,overflow: TextOverflow.ellipsis,
                              style: rubikRegular.copyWith(fontSize: Dimensions.fontSizeSmall, color: Theme.of(context).disabledColor))),
                        ],
                      ),
                    ),
                    ]))])),

            ]),
          ),
        );
      }
    );
  }
}
