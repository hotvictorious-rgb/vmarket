import 'dart:async';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_asset_image_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/paginated_list_view_widget.dart';
import 'package:sixvalley_vendor_app/features/chat/controllers/chat_controller.dart';
import 'package:sixvalley_vendor_app/features/chat/domain/models/message_model.dart';
import 'package:sixvalley_vendor_app/features/chat/screens/media_viewer_screen.dart';
import 'package:sixvalley_vendor_app/features/chat/widgets/chat_shimmer_widget.dart';
import 'package:sixvalley_vendor_app/features/splash/controllers/splash_controller.dart';
import 'package:sixvalley_vendor_app/helper/date_converter.dart';
import 'package:sixvalley_vendor_app/helper/image_size_checker.dart';
import 'package:sixvalley_vendor_app/localization/language_constrants.dart';
import 'package:sixvalley_vendor_app/utill/app_constants.dart';
import 'package:sixvalley_vendor_app/utill/dimensions.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_app_bar_widget.dart';
import 'package:sixvalley_vendor_app/features/chat/widgets/message_bubble_widget.dart';
import 'package:sixvalley_vendor_app/features/chat/widgets/send_message_widget.dart';
import 'package:sixvalley_vendor_app/features/chat/widgets/whatsapp_chat_wallpaper.dart';
import 'package:sixvalley_vendor_app/utill/images.dart';
import 'package:sixvalley_vendor_app/utill/styles.dart';

import '../../../main.dart';

class ChatScreen extends StatefulWidget {
  final String name;
  final int? userId;
  final int? orderId;
  final String? orderStatus;
  const ChatScreen({super.key, required this.userId, this.name = '', this.orderId, this.orderStatus});

  @override
  State<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final ImagePicker picker = ImagePicker();
  final ScrollController _scrollController = ScrollController();
  Timer? _liveSyncTimer;

  @override
  void initState() {
    Provider.of<ChatController>(context, listen: false).getMessageList(widget.userId, 1);
    Provider.of<ChatController>(context, listen: false).setEmojiPickerValue(false, notify: false);
    _startLiveSync();
    super.initState();
  }

  @override
  void dispose() {
    _liveSyncTimer?.cancel();
    super.dispose();
  }

  void _startLiveSync() {
    _liveSyncTimer = Timer.periodic(const Duration(seconds: 4), (timer) {
      if (mounted) {
        Provider.of<ChatController>(context, listen: false).getMessageList(widget.userId, 1, reload: false);
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final bool isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      body: Consumer<ChatController>(builder: (context, chatController, child) {
        final bool isOrderTerminated = widget.orderStatus == 'delivered' || widget.orderStatus == 'canceled' || widget.orderStatus == 'returned';
        final bool isChatActive = (chatController.messageModel?.isActive ?? true) && !isOrderTerminated;

        return Column(children: [
          CustomAppBarWidget(title: widget.name),
          if (widget.orderId != null)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              decoration: BoxDecoration(
                color: isDark ? const Color(0xFF1E2630) : const Color(0xFFF3E5F5),
                border: Border(bottom: BorderSide(color: isDark ? Colors.white10 : const Color(0xFFE1BEE7))),
              ),
              child: Row(
                children: [
                  const Icon(Icons.local_shipping_outlined, size: 18, color: Color(0xFF6A1B9A)),
                  const SizedBox(width: 8),
                  Text(
                    'Order #${widget.orderId}',
                    style: robotoBold.copyWith(color: const Color(0xFF6A1B9A), fontSize: 13),
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
                        style: robotoBold.copyWith(
                          color: isOrderTerminated ? Colors.grey : const Color(0xFF00A884),
                          fontSize: 10,
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ),
          const SizedBox(height: 2),
          chatController.messageModel != null ?
          Expanded(child: WhatsAppChatWallpaper(
            isDark: isDark,
            child: (chatController.messageModel!.message != null && chatController.messageModel!.message!.isNotEmpty) ?
            SingleChildScrollView(
              reverse: true,
              controller : _scrollController,
            child: PaginatedListViewWidget(
              reverse: true,
              scrollController: _scrollController,
              totalSize: chatController.messageModel?.totalSize,
              offset: chatController.messageModel?.offset,
              onPaginate: (int? offset) async => await chatController.getMessageList( widget.userId, offset!, reload: false),

              itemView : ListView.builder(
                itemCount: chatController.messageModel?.message?.length,
                padding: EdgeInsets.zero,
                physics: const NeverScrollableScrollPhysics(),
                shrinkWrap: true,
                reverse: true,
                itemBuilder: (context, index) {

                  return Column(
                      crossAxisAlignment: chatController.messageModel?.message?[index].sentBySeller ?? false ? CrossAxisAlignment.end : CrossAxisAlignment.start, mainAxisSize: MainAxisSize.min,
                      children: [
                        if(_willShowDate(index, chatController.messageModel) != null)
                          Center(
                            child: Padding(
                              padding: const EdgeInsets.symmetric(
                                horizontal: Dimensions.paddingSizeExtraSmall,
                                vertical: Dimensions.paddingSizeSmall,
                              ),
                              // DateConverter.customTime(DateTime.parse(chat!.createdAt!))
                              child: Text( DateConverter.dateStringMonthYear(DateTime.tryParse(chatController.messageModel!.message![index].createdAt!)),
                                  style: robotoMedium.copyWith(fontSize: Dimensions.fontSizeSmall,
                                      color: Theme.of(context).textTheme.bodyLarge!.color!.withValues(alpha:0.5)),
                                  textDirection: TextDirection.ltr),
                            ),
                          ),

                        MessageBubbleWidget(
                          message: chatController.messageModel!.message![index],
                          previous: (index != 0) ? chatController.messageModel!.message![index -1 ] : null,
                          next: index == (chatController.messageModel!.message!.length -1) ?  null : chatController.messageModel!.message![index + 1],
                        ),

                      ]);
                },
              ),
            ),
          ) : const SizedBox.shrink()),
          ) : const Expanded(child: ChatShimmerWidget()),
          const SizedBox(height: Dimensions.paddingSizeExtraSmall),

          chatController.isLoading ? Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeSmall, vertical: Dimensions.paddingSizeExtraSmall),
                child: AnimatedContainer(
                  curve: Curves.fastOutSlowIn,
                  duration: const Duration(milliseconds: 500),
                  height: chatController.isLoading ? 25.0 : 0.0,
                  child: Text(getTranslated('sending', context)!, style: robotoRegular.copyWith(color: Theme.of(context).primaryColor.withValues(alpha:.75)),),
                ),
              ),
            ],
          ) : const SizedBox(),



          chatController.hasPicked ?
          Container(
            color: chatController.isLoading == false && ((chatController.pickedMediaFileModelList != null && chatController.pickedMediaFileModelList!.isNotEmpty) || (chatController.pickedFiles != null && chatController.pickedFiles!.isNotEmpty)) ?
            Theme.of(context).primaryColor.withValues(alpha:0.1) : null,
            height: (chatController.pickedFIleCrossMaxLimit || chatController.pickedFIleCrossMaxLength || chatController.singleFIleCrossMaxLimit) ? 130 : 110,
            width: MediaQuery.of(context).size.width,
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: Dimensions.paddingSizeDefault),
            child: const Center(child: CircularProgressIndicator()),
          ) : chatController.pickedMediaFileModelList != null && chatController.pickedMediaFileModelList!.isNotEmpty && chatController.isLoading == false ?
          Container(
            color:  chatController.isLoading == false && ((chatController.pickedMediaFileModelList != null && chatController.pickedMediaFileModelList!.isNotEmpty) || (chatController.pickedFiles != null && chatController.pickedFiles!.isNotEmpty)) ?
            Theme.of(context).primaryColor.withValues(alpha:0.1) : null,
            height:  (chatController.pickedFIleCrossMaxLimit || chatController.pickedFIleCrossMaxLength || chatController.singleFIleCrossMaxLimit) ? 130 : 110, width: MediaQuery.of(context).size.width,
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: Dimensions.paddingSizeExtraSmall),
            child: Column(
              mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                SizedBox(
                  height: 80,
                  child: ListView.builder(scrollDirection: Axis.horizontal,
                    itemBuilder: (context,index){
                      return  Stack(children: [
                        Padding(padding: const EdgeInsets.only(top: Dimensions.paddingSizeExtraSmall, left: Dimensions.paddingSizeVeryTiny, right: Dimensions.paddingSize, bottom: Dimensions.paddingSizeVeryTiny),
                            child: ClipRRect(borderRadius: BorderRadius.circular(10),
                                child: SizedBox(height: 80, width: 80,
                                    child: Image.file(File(chatController.pickedMediaFileModelList![index].thumbnailPath ?? ''), fit: BoxFit.cover),
                                ),
                            ),
                        ),


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
                                padding: const EdgeInsets.all(Dimensions.paddingSizeExtraSmall),
                                decoration: const BoxDecoration(
                                  color: Colors.white,
                                  shape: BoxShape.circle,
                                ),
                                child: Icon(Icons.play_arrow, color: Theme.of(context).primaryColor, size: 30),
                              ),
                            )),
                          ),

                        Positioned(
                          right: 5,
                          top: 0,
                          child: InkWell(
                              splashColor: Colors.transparent,
                              child: const CustomAssetImageWidget(Images.crossImageChat),
                              onTap: () => chatController.pickMultipleMedia(true,index: index)),
                        ),
                      ],
                      );},
                    itemCount: chatController.pickedMediaFileModelList!.length,
                  ),
                ),

                if(chatController.pickedFIleCrossMaxLimit || chatController.pickedImageCrossMaxLength || chatController.singleFIleCrossMaxLimit)
                  Text( "${chatController.pickedImageCrossMaxLength ? "• ${getTranslated('can_not_select_more_than', context)!} ${AppConstants.maxLimitOfTotalFileSent.floor()} 'files' " :""} "
                      "${chatController.pickedFIleCrossMaxLimit ? "• ${getTranslated('total_images_size_can_not_be_more_than', context)!} ${AppConstants.maxLimitOfFileSentINConversation.floor()} MB" : ""} "
                      "${chatController.singleFIleCrossMaxLimit ? "• ${getTranslated('single_file_size_can_not_be_more_than', context)!} "
                      "${(Provider.of<SplashController>(Get.context!, listen: false).configModel?.systemGeneralFileUploadMaxSize ?? AppConstants.maxSizeOfASingleFile)} MB" : ""} ",
                    style: robotoRegular.copyWith(
                      fontSize: Dimensions.fontSizeSmall, color: Theme.of(context).colorScheme.error,
                    ),
                  ),
              ],
            )
          ) : const SizedBox(),

          chatController.pickedFiles != null && chatController.pickedFiles!.isNotEmpty && chatController.isLoading == false ?
          ColoredBox(
            color: Theme.of(context).primaryColor.withValues(alpha:0.1),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 15, vertical: 5),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Row(children: []),
                  SizedBox(height: 70,
                    child: ListView.separated(
                      shrinkWrap: true, scrollDirection: Axis.horizontal,
                      padding: const EdgeInsets.only(bottom: 5),
                      separatorBuilder: (context, index) => const SizedBox(width: Dimensions.paddingSizeDefault),
                      itemCount: chatController.pickedFiles!.length,
                      itemBuilder: (context, index){
                        String fileSize =  ImageValidationHelper.getFileSizeFromPlatformFileToString(chatController.pickedFiles![index]);
                        return Container(width: 180,
                          decoration: BoxDecoration(
                            color: Theme.of(context).cardColor,
                            borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
                          ),
                          padding: const EdgeInsets.only(left: 10, right: 5),
                          child: Row(crossAxisAlignment: CrossAxisAlignment.center,children: [

                            Image.asset(Images.fileIcon,height: 30, width: 30,),
                            const SizedBox(width: Dimensions.paddingSizeExtraSmall,),

                            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start,mainAxisAlignment: MainAxisAlignment.center, children: [

                              Text(chatController.pickedFiles![index].name,
                                maxLines: 1, overflow: TextOverflow.ellipsis,
                                style: robotoBold.copyWith(fontSize: Dimensions.fontSizeDefault),
                              ),

                              Text(fileSize, style: robotoRegular.copyWith(fontSize: Dimensions.fontSizeDefault,
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
                                    size: Dimensions.paddingSizeLarge,
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

                  if(chatController.pickedFIleCrossMaxLimit || chatController.pickedFIleCrossMaxLength || chatController.singleFIleCrossMaxLimit)
                    Text( "${chatController.pickedFIleCrossMaxLength ? "• ${getTranslated('can_not_select_more_than', context)!} ${AppConstants.maxLimitOfTotalFileSent.floor()} 'files' " :""} "
                        "${chatController.pickedFIleCrossMaxLimit ? "• ${getTranslated('total_images_size_can_not_be_more_than', context)!} ${Provider.of<SplashController>(Get.context!, listen: false).configModel?.systemGeneralFileUploadMaxSize ?? AppConstants.maxLimitOfFileSentINConversation.floor()} MB" : ""} "
                        "${chatController.singleFIleCrossMaxLimit ? "• ${getTranslated('single_file_size_can_not_be_more_than', context)!} ${Provider.of<SplashController>(Get.context!, listen: false).configModel?.systemGeneralFileUploadMaxSize ?? AppConstants.maxSizeOfASingleFile.floor()} MB" : ""} ",
                      style: robotoRegular.copyWith(
                        fontSize: Dimensions.fontSizeSmall, color: Theme.of(context).colorScheme.error,
                      ),
                    ),
                ],
              ),
            ),
          ) : const SizedBox(),

          if (!isChatActive)
            Padding(
              padding: const EdgeInsets.fromLTRB(Dimensions.paddingSizeDefault, 0, Dimensions.paddingSizeDefault, Dimensions.paddingSizeDefault),
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                decoration: BoxDecoration(
                  color: isDark ? const Color(0xFF1F2C34) : const Color(0xFFF5F5F5),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: isDark ? Colors.white12 : Colors.grey.withValues(alpha: 0.2)),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.lock_outline_rounded, size: 18, color: Colors.grey),
                    const SizedBox(width: 8),
                    Text(
                      getTranslated('order_delivered_chat_closed', context) ?? 'This order is delivered. Chat is closed.',
                      style: robotoRegular.copyWith(color: isDark ? Colors.white60 : Colors.grey[700], fontSize: 13),
                    ),
                  ],
                ),
              ),
            )
          else if (chatController.userTypeIndex != 0) SafeArea(top: false, child: SizedBox(
            height: chatController.openEmojiPicker ? 360 : 60,
            child: SendMessageWidget(id: widget.userId, orderId: widget.orderId),
          )) else Padding(
            padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
            child: Center(
              child: Text(getTranslated('chatting_is_disabled', context) ?? 'Chat is disabled for customers', style: robotoRegular),
            ),
          ),
        ]);
      }),
    );
  }


  String? _willShowDate(int index, MessageModel? messageModel) {

    if(messageModel?.message == null) return null;

    final Message currentMessage = messageModel!.message![index];
    final nextMessage = index < ((messageModel.message?.length ?? 0) - 1) ? messageModel.message![index + 1] : null;

    DateTime? currentMessageDate = currentMessage.createdAt == null ? null : DateTime.tryParse(currentMessage.createdAt!);
    DateTime? nextMessageDate = nextMessage?.createdAt == null ? null : DateTime.tryParse(nextMessage!.createdAt!);
    bool isFirst = index == ((messageModel.message?.length ?? 0) - 1);

    if (isFirst || (nextMessageDate?.day != currentMessageDate?.day)) {
      return DateConverter.dateStringMonthYear(currentMessageDate);
    }
    return null;
  }
}



