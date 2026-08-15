import 'package:flutter/material.dart';
import 'package:sixvalley_delivery_boy/features/chat/controllers/chat_controller.dart';
import 'package:sixvalley_delivery_boy/common/controllers/localization_controller.dart';
import 'package:sixvalley_delivery_boy/helper/color_helper.dart';
import 'package:sixvalley_delivery_boy/theme/controllers/theme_controller.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_search_widget.dart';
import 'package:sixvalley_delivery_boy/features/chat/widgets/chat_type_button_widget.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';

class ChatHeaderWidget extends StatefulWidget {
  const ChatHeaderWidget({Key? key}) : super(key: key);

  @override
  State<ChatHeaderWidget> createState() => _ChatHeaderWidgetState();
}

class _ChatHeaderWidgetState extends State<ChatHeaderWidget> {
  final TextEditingController _textEditingController = TextEditingController();
  @override
  Widget build(BuildContext context) {
    return GetBuilder<ChatController>(
        builder: (chat) {
          return Padding(padding:  EdgeInsets.symmetric(horizontal : Dimensions.paddingSizeExtraLarge),
            child: Container(height: 55, decoration: BoxDecoration(
                  color: (
                    Get.find<ThemeController>().darkTheme ?
                    ColorHelper.blendColors(Colors.white, Theme.of(context).primaryColor, 0.9):
                    ColorHelper.darken(Theme.of(context).primaryColor, 0.1)
                  ),
                  borderRadius: BorderRadius.circular(Dimensions.searchRadius)),
                child: Stack(children: [

                    Positioned(child: Align(
                        alignment: Get.find<LocalizationController>().isLtr? Alignment.centerRight : Alignment.centerLeft,
                        child: Padding(
                          padding: EdgeInsets.only(right: Dimensions.paddingSizeSmall),
                          child: ListView(
                            shrinkWrap: true,
                            scrollDirection: Axis.horizontal,
                            children: [
                              ChatTypeButtonWidget(text: 'seller'.tr, index: 0),
                              ChatTypeButtonWidget(text: 'customer'.tr, index: 1),
                              ChatTypeButtonWidget(text: 'admin'.tr, index: 3)]),
                        ))),

                    CustomSearchWidget(
                      width: MediaQuery.of(context).size.width,
                      textController: _textEditingController,
                      onSuffixTap: () {
                        // _textEditingController.clear();
                      },
                      color: Get.isDarkMode ? Theme.of(context).highlightColor : Theme.of(context).cardColor,
                      helpText: "search_by_name".tr,
                      style: rubikRegular.copyWith(fontSize: Dimensions.fontSizeDefault),
                      hintStyle: rubikRegular.copyWith(fontSize: Dimensions.fontSizeDefault, color: Theme.of(context).hintColor,),
                      autoFocus: true,
                      closeSearchOnSuffixTap: true,
                      onChanged: (value){
                        if(value != null){
                          chat.searchConversationList(value);
                        }
                      },
                      animationDurationInMilli: 200,
                      rtl: !Get.find<LocalizationController>().isLtr,
                    ),
                  ],
                )),
          );
        }
    );
  }
}
