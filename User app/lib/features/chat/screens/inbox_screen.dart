import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/features/chat/domain/models/chat_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/chat/widgets/conversation_tabview.dart';
import 'package:flutter_sixvalley_ecommerce/features/profile/controllers/profile_contrroller.dart';
import 'package:flutter_sixvalley_ecommerce/helper/route_healper.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/controllers/auth_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/chat/controllers/chat_controller.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:flutter_sixvalley_ecommerce/utill/images.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_app_bar_widget.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/no_internet_screen_widget.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/not_loggedin_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/chat/widgets/chat_item_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/chat/widgets/inbox_shimmer_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/chat/widgets/search_inbox_widget.dart';
import 'package:provider/provider.dart';


class InboxScreen extends StatefulWidget {
  final bool isBackButtonExist;
  final bool fromNotification;
  final bool fromDashboard;
  final int initIndex;
  const InboxScreen({super.key, this.isBackButtonExist = true,  this.fromNotification = false, this.initIndex = 0, this.fromDashboard = false});

  @override
  State<InboxScreen> createState() => _InboxScreenState();
}

class _InboxScreenState extends State<InboxScreen> with SingleTickerProviderStateMixin{

  TextEditingController searchController = TextEditingController();
  TabController? _tabController;

  late bool isGuestMode;
  @override
  void initState() {
    final ChatController chatController = Provider.of<ChatController>(context, listen: false);

    chatController.setUserTypeIndex(context, 0, isUpdate: false);
    chatController.resetIsSearchComplete(isUpdate: false);

    isGuestMode = !Provider.of<AuthController>(context, listen: false).isLoggedIn();
    if(!isGuestMode) {
      _tabController = TabController(length: 2, initialIndex: widget.initIndex, vsync: this);
      _tabController?.addListener((){
        if(searchController.text.isNotEmpty){
          searchController.clear();
          chatController.resetIsSearchComplete(isUpdate: false);
        }
      });
    }

    if(widget.fromNotification) {
      chatController.setUserTypeIndex(context, 0, isUpdate: false);
      chatController.getChatList(1, reload: false, userType: 0);
    }

    if (!isGuestMode && !widget.fromDashboard) {
      if(Provider.of<ProfileController>(context, listen: false).userInfoModel == null) {
        Provider.of<ProfileController>(context, listen: false).getUserInfo(context);
      }
    }
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    final Size size = MediaQuery.of(context).size;

    return Scaffold(
      appBar: CustomAppBar(title: getTranslated('inbox', context), isBackButtonExist: !widget.fromDashboard,
      onBackPressed: (){
        if(Navigator.of(context).canPop()){
          Navigator.of(context).pop();
        }else{
          RouterHelper.getDashboardRoute(action: RouteAction.pushNamedAndRemoveUntil);
        }
      }),
      body: Consumer<ChatController>(
        builder: (context, chat, _) {
          return Column(children: [
            if(!isGuestMode)
            Consumer<ChatController>(
              builder: (context, chat, _) {
                return Padding(padding: const EdgeInsets.fromLTRB( Dimensions.homePagePadding,
                  Dimensions.paddingSizeSmall, Dimensions.homePagePadding, 0),
                  child: SearchInboxWidget(hintText: getTranslated('search', context)));
              }),

            if(!isGuestMode)
              ConversationListTabview(tabController: _tabController),

            Expanded(child: isGuestMode ? NotLoggedInWidget(message: getTranslated('to_communicate_with_vendors', context),
              fromPage: widget.fromDashboard ? '${RouterHelper.dashboardScreen}?page=inbox' : RouterHelper.inboxScreen,
              onLoginSuccess: !widget.fromDashboard ? () {
                RouterHelper.getInboxScreenRoute(action: RouteAction.pushReplacement);
              } : null,
            ) :

                RefreshIndicator(
                  onRefresh: () async {
                    searchController.clear();
                    await chat.getChatList(1, userType: 0);
                  },
                child: Consumer<ChatController>(
                  builder: (context, chatProvider, child) {
                    ChatModel? cahtModel;

                    if(chatProvider.isSearchComplete) {
                      if (chatProvider.userTypeIndex == 0) cahtModel = chatProvider.searchDeliverymanChatModel;
                      else cahtModel = chatProvider.searchAdminChatModel;
                    } else {
                      if (chatProvider.userTypeIndex == 0) cahtModel = chatProvider.deliverymanChatModel;
                      else cahtModel = chatProvider.adminChatModel;
                    }

                  return cahtModel != null ? (cahtModel.chat != null && cahtModel.chat!.isNotEmpty) ?
                    ListView.builder(
                      itemCount: cahtModel.chat?.length,
                      padding: const EdgeInsets.all(0),
                      itemBuilder: (context, index) {
                        return ChatItemWidget(
                          chat: cahtModel?.chat![index],
                          chatProvider: chat,
                          callBack: (){
                            if(chatProvider.isSearchComplete){
                              chatProvider.searchDeliverymanChatModel!.chat![index].unseenMessageCount = 0;
                            } else {
                              chatProvider.deliverymanChatModel!.chat![index].unseenMessageCount = 0;
                            }
                          },
                        );
                      },
                    ) :  NoInternetOrDataScreenWidget(
                    padding: EdgeInsets.only(top: size.height * 0.15),
                    isNoInternet: false,
                    message: chatProvider.userTypeIndex == 0 ? 'no_deliveryman_found' : chatProvider.userTypeIndex == 1 ? 'no_vendor_found' : 'no_admin_found',
                    icon: chatProvider.userTypeIndex == 0 ? Images.deliverymanPlaceholder : Images.chatImage,
                  ) : const InboxShimmerWidget();
                })
              )
            ),
          ]);
        }
      ),
    );
  }
}



