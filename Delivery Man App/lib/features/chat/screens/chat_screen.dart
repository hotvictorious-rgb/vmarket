
import 'dart:io';

import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_image_widget.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_loader_widget.dart';
import 'package:sixvalley_delivery_boy/features/chat/controllers/chat_controller.dart';
import 'package:sixvalley_delivery_boy/features/chat/screens/media_viewer_screen.dart';
import 'package:sixvalley_delivery_boy/features/chat/widgets/message_list_view_widget.dart';
import 'package:sixvalley_delivery_boy/features/chat/widgets/message_sendig_section_widget.dart';
import 'package:sixvalley_delivery_boy/features/chat/widgets/whatsapp_chat_wallpaper.dart';
import 'package:sixvalley_delivery_boy/features/splash/controllers/splash_controller.dart';
import 'package:sixvalley_delivery_boy/helper/image_size_checker.dart';
import 'package:sixvalley_delivery_boy/utill/app_constants.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';


class ChatScreen extends StatefulWidget {
  final int? userId;
  final String? name;
  final String? image;
  final int? orderId;
  final String? orderStatus;
  const ChatScreen({Key? key, required this.userId, this.name = 'chat', this.image = '', this.orderId, this.orderStatus}) : super(key: key);

  @override
  State<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final ScrollController _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    Get.find<ChatController>().getChats(1, widget.userId,firstLoad: true);
  }

  @override
  Widget build(BuildContext context) {
    final bool isDarkTheme = Get.isDarkMode;

    return GetBuilder<ChatController>(builder: (chatController) {
      return PopScope(
        canPop: true,
        onPopInvokedWithResult: (didPop, _) {
          Get.find<ChatController>().getConversationList(1);
        },
        child: Scaffold(
          appBar: AppBar(
            backgroundColor: isDarkTheme ? const Color(0xFF1F2C34) : const Color(0xFF4A148C),
            titleSpacing: 0,
            elevation: 1,
            leading: InkWell(
              highlightColor: Theme.of(context).primaryColor.withValues(alpha:0),
              splashColor: Theme.of(context).primaryColor.withValues(alpha:0),
              onTap: ()=> Navigator.pop(context),
              child: const Icon(CupertinoIcons.back, color: Colors.white),
            ),
            title: Row(children: [
              Stack(
                clipBehavior: Clip.none,
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(100),
                    child: Container(
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(100),
                        border: Border.all(width: 1, color: const Color(0xFFFFD700)),
                      ),
                      height: 38,
                      width: 38,
                      child: CustomImageWidget(image: widget.image ?? ''),
                    ),
                  ),
                  Positioned(
                    bottom: 0,
                    right: 0,
                    child: Container(
                      width: 10,
                      height: 10,
                      decoration: BoxDecoration(
                        color: const Color(0xFF25D366),
                        shape: BoxShape.circle,
                        border: Border.all(color: Colors.white, width: 1.5),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(width: 10),

              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      widget.name ?? '',
                      style: rubikBold.copyWith(fontSize: Dimensions.fontSizeDefault + 1, color: Colors.white),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    Text(
                      'online',
                      style: rubikRegular.copyWith(fontSize: 11, color: const Color(0xFFFFD700).withValues(alpha: 0.9)),
                    ),
                  ],
                ),
              ),
            ]),
          ),

          body: WhatsAppChatWallpaper(
            isDark: isDarkTheme,
            child: SizedBox(
              width: MediaQuery.of(context).size.width,
              child: Builder(
                builder: (context) {
                  final bool isOrderTerminated = widget.orderStatus == 'delivered' || widget.orderStatus == 'canceled' || widget.orderStatus == 'returned';
                  final bool isChatActive = (chatController.messageModel?.isActive ?? true) && !isOrderTerminated;

                  return Column(children: [
                    if (widget.orderId != null)
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                        decoration: BoxDecoration(
                          color: isDarkTheme ? const Color(0xFF1E2630) : const Color(0xFFF3E5F5),
                          border: Border(bottom: BorderSide(color: isDarkTheme ? Colors.white10 : const Color(0xFFE1BEE7))),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.local_shipping_outlined, size: 18, color: Color(0xFF6A1B9A)),
                            const SizedBox(width: 8),
                            Text(
                              'Order #${widget.orderId}',
                              style: rubikBold.copyWith(color: const Color(0xFF6A1B9A), fontSize: 13),
                            ),
                            if (widget.orderStatus != null && widget.orderStatus!.isNotEmpty) ...[
                              const SizedBox(width: 8),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                decoration: BoxDecoration(
                                  color: isOrderTerminated
                                      ? Colors.grey.withValues(alpha: 0.2)
                                      : const Color(0xFF00A884).withValues(alpha: 0.15),
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: Text(
                                  widget.orderStatus!.replaceAll('_', ' ').toUpperCase(),
                                  style: rubikBold.copyWith(
                                    color: isOrderTerminated ? Colors.grey : const Color(0xFF00A884),
                                    fontSize: 10,
                                  ),
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),

                    chatController.messageModel != null ?
                     Expanded(child: (chatController.messageModel!.message != null && chatController.messageModel!.message!.isNotEmpty) ?
                        MessageListViewWidget( scrollController: _scrollController, userId: widget.userId) :
                        const SizedBox()): Expanded(child: CustomLoaderWidget(height: Get.height-300,)),

                chatController.hasPicked ?
                Container(
                    color:  chatController.isLoading == false && ((chatController.pickedMediaFileModelList != null && chatController.pickedMediaFileModelList!.isNotEmpty) || (chatController.pickedFiles != null && chatController.pickedFiles!.isNotEmpty)) ?
                    Theme.of(context).primaryColor.withValues(alpha:0.1) : null,
                    height: (chatController.pickedFIleCrossMaxLimit || chatController.pickedFIleCrossMaxLength || chatController.singleFIleCrossMaxLimit) ? 130 : 110, width: MediaQuery.of(context).size.width,
                    padding: EdgeInsets.symmetric(horizontal: 10, vertical: Dimensions.paddingSizeDefault),
                    child: const Center(child: CircularProgressIndicator()))
                    : (chatController.pickedMediaFileModelList?.isNotEmpty ?? false) && chatController.isSending == false ?
                Container(
                  color:  chatController.isLoading == false && ((chatController.pickedMediaFileModelList !=null && chatController.pickedMediaFileModelList!.isNotEmpty) || (chatController.pickedFiles != null && chatController.pickedFiles!.isNotEmpty)) ?
                  Theme.of(context).primaryColor.withValues(alpha:0.1) : null,
                  height: (chatController.pickedFIleCrossMaxLimit || chatController.pickedImageCrossMaxLength || chatController.singleFIleCrossMaxLimit) ? 130 : 110, width: MediaQuery.of(context).size.width,
                  padding: EdgeInsets.symmetric(horizontal: 10, vertical: Dimensions.paddingSizeDefault),
                  child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
                      SizedBox(
                        height: 80,
                        child: ListView.builder(scrollDirection: Axis.horizontal,
                            itemBuilder: (context,index){
                              return  Padding(
                                padding: EdgeInsets.only(top: Dimensions.paddingSizeSmall, right: Dimensions.paddingSizeMin ),
                                child: Stack(children: [
                                  Padding(padding: const EdgeInsets.symmetric(horizontal: 5,),
                                      child: ClipRRect(borderRadius: BorderRadius.circular(10),
                                        child: SizedBox(height: 80, width: 80,
                                          child: Image.file(File(chatController.pickedMediaFileModelList![index].thumbnailPath ?? ''), fit: BoxFit.cover)))),

                                  if(chatController.pickedMediaFileModelList?[index].isVideo ?? false)
                                    Positioned.fill(
                                      child: Align(alignment: Alignment.center, child: InkWell(
                                        onTap: () => Navigator.push(context, MaterialPageRoute(
                                          builder: (_) => MediaViewerScreen(
                                            clickedIndex: index,
                                            localMedia: chatController.getXFileFromMediaFileModel(chatController.pickedMediaFileModelList ?? []),
                                          ),
                                        )),
                                        child: Container(
                                          padding: EdgeInsets.all(Dimensions.paddingSizeExtraSmall),
                                          decoration: const BoxDecoration(
                                            color: Colors.white,
                                            shape: BoxShape.circle,
                                          ),
                                          child: Icon(Icons.play_arrow, color: Theme.of(context).primaryColor, size: 30),
                                        ),
                                      )),
                                    ),

                                  Positioned(
                                    right: 0,
                                    top: 0,
                                    child: Transform.translate(
                                      offset: const Offset(2, -8),
                                      child: InkWell(
                                          child: Image.asset(Images.imageCancel, width: 20, height: 20,),
                                          onTap: () => chatController.pickMultipleMedia(true, index: index)),
                                    ),
                                  )]),
                              );},
                            itemCount: chatController.pickedMediaFileModelList!.length),
                      ),

                      if(chatController.pickedFIleCrossMaxLimit || chatController.pickedImageCrossMaxLength || chatController.singleFIleCrossMaxLimit)
                        Text( "${chatController.pickedImageCrossMaxLength ? "• ${"can_not_select_more_than".tr} ${AppConstants.maxLimitOfTotalFileSent.floor()} ${'files'.tr}" :""} "
                            "${chatController.pickedFIleCrossMaxLimit ? "• ${'total_images_size_can_not_be_more_than'.tr} ${AppConstants.maxLimitOfFileSentINConversation.floor()} MB" : ""} "
                            "${chatController.singleFIleCrossMaxLimit ? "• ${'single_file_size_can_not_be_more_than'.tr} "
                            "${(chatController.getExtractSizeInMB(Get.find<SplashController>().configModel?.serverUploadMaxFileSize ?? '') ?? AppConstants.maxSizeOfASingleFile)} MB" : ""} ",
                          style: rubikRegular.copyWith(
                            fontSize: Dimensions.fontSizeSmall, color: Theme.of(context).colorScheme.error.withValues(alpha:0.7),
                          ),
                        ),
                    ],
                  )

                ) : const SizedBox(),


                (chatController.pickedFiles?.isNotEmpty ?? false) && chatController.isLoading == false && chatController.isSending == false ?
                ColoredBox(
                  color: Theme.of(context).primaryColor.withValues(alpha:0.1),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 0, vertical: 5),
                    child: Wrap(
                      alignment: WrapAlignment.start,
                      children: [
                        SizedBox(height: 70,
                          width: MediaQuery.of(context).size.width,
                          child: Padding(
                            padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                            child: ListView.separated(
                              shrinkWrap: true, scrollDirection: Axis.horizontal,
                              padding: const EdgeInsets.only(bottom: 5),
                              separatorBuilder: (context, index) =>  SizedBox(width: Dimensions.paddingSizeDefault),
                              itemCount: chatController.pickedFiles!.length,
                              itemBuilder: (context, index){
                                String fileSize =  ImageSize.getFileSizeFromPlatformFileToString(chatController.pickedFiles![index]);
                                return Container(width: 180,
                                  decoration: BoxDecoration(
                                    color: Theme.of(context).cardColor,
                                    borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
                                  ),
                                  padding: const EdgeInsets.only(left: 10, right: 5),
                                  child: Row(crossAxisAlignment: CrossAxisAlignment.center, children: [

                                    Image.asset(Images.fileIcon,height: 30, width: 30,),
                                    SizedBox(width: Dimensions.paddingSizeExtraSmall),

                                    Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start,mainAxisAlignment: MainAxisAlignment.center, children: [

                                      Text(chatController.pickedFiles![index].name,
                                        maxLines: 1, overflow: TextOverflow.ellipsis,
                                        style: rubikBold.copyWith(fontSize: Dimensions.fontSizeDefault),
                                      ),

                                      Text(fileSize, style: rubikRegular.copyWith(fontSize: Dimensions.fontSizeDefault,
                                        color: Theme.of(context).hintColor,
                                      )),
                                    ])),


                                    InkWell(
                                      onTap: () {
                                        chatController.pickOtherFile(true, index: index);
                                      },
                                      child: Padding(padding: const EdgeInsets.only(top: 5),
                                        child: Align(alignment: Alignment.topRight,
                                          child: Icon(Icons.close,
                                            size: Dimensions.paddingSizeOverLarge,
                                            color: Theme.of(context).hintColor,
                                          ),
                                        ),
                                      ),
                                    )
                                  ]),
                                );
                              },
                            ),
                          ),
                        ),

                        // print('');
                        // print();
                        // print();

                        if(chatController.pickedFIleCrossMaxLimit || chatController.pickedFIleCrossMaxLength || chatController.singleFIleCrossMaxLimit)
                          Text( "${chatController.pickedFIleCrossMaxLength ? "• ${"can_not_select_more_than".tr} ${AppConstants.maxLimitOfTotalFileSent.floor()} ${'files'.tr}" :""} "
                              "${chatController.pickedFIleCrossMaxLimit ? "• ${'total_images_size_can_not_be_more_than'.tr} ${AppConstants.maxLimitOfFileSentINConversation.floor()} MB" : ""} "
                              "${chatController.singleFIleCrossMaxLimit ? "• ${'single_file_size_can_not_be_more_than'.tr} ${AppConstants.maxSizeOfASingleFile.floor()} MB" : ""} ",
                            style: rubikRegular.copyWith(
                              fontSize: Dimensions.fontSizeSmall, color: Theme.of(context).colorScheme.error.withValues(alpha:0.7),
                            ),
                          ),
                      ],
                    ),
                  ),
                ) : const SizedBox(),



                chatController.isSending ? Row(
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    Padding(
                      padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                      child: AnimatedContainer(
                        curve: Curves.fastOutSlowIn,
                        duration: const Duration(milliseconds: 500),
                        height: chatController.isSending ? 25.0 : 0.0,
                        child: Padding(
                          padding: EdgeInsets.only(
                            top: chatController.isSending ?
                            Dimensions.paddingSizeExtraSmall : 0.0,
                          ),
                          child: Text('sending'.tr, style: rubikRegular.copyWith(color: Theme.of(context).primaryColor.withValues(alpha:.75)),),
                        ),
                      ),
                    ),
                  ],
                ) : const SizedBox(),


                if (!isChatActive)
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                    child: Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                      decoration: BoxDecoration(
                        color: isDarkTheme ? const Color(0xFF1F2C34) : const Color(0xFFF5F5F5),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: isDarkTheme ? Colors.white12 : Colors.grey.withValues(alpha: 0.2)),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(Icons.lock_outline_rounded, size: 18, color: Colors.grey),
                          const SizedBox(width: 8),
                          Text(
                            'This order is delivered. Chat is closed.'.tr,
                            style: rubikRegular.copyWith(color: isDarkTheme ? Colors.white60 : Colors.grey[700], fontSize: 13),
                          ),
                        ],
                      ),
                    ),
                  )
                else
                  MessageSendingSectionWidget(userId: widget.userId),
              ]);
            },
          ),
        ),
      ),
    ),
  );
});
  }
}
