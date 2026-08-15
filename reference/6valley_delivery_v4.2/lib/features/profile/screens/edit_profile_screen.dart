
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/features/auth/controllers/auth_controller.dart';
import 'package:sixvalley_delivery_boy/features/profile/controllers/profile_controller.dart';
import 'package:sixvalley_delivery_boy/features/profile/domain/models/userinfo_model.dart';
import 'package:sixvalley_delivery_boy/helper/color_helper.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_button_widget.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_snackbar_widget.dart';
import 'package:sixvalley_delivery_boy/features/profile/widgets/account_info_widget.dart';
import 'package:sixvalley_delivery_boy/features/profile/widgets/general_info_widget.dart';
import 'package:sixvalley_delivery_boy/features/profile/widgets/profile_header.dart';


class ProfileEditScreen extends StatefulWidget {
  const ProfileEditScreen({Key? key}) : super(key: key);

  @override
  _ProfileEditScreenState createState() => _ProfileEditScreenState();
}

class _ProfileEditScreenState extends State<ProfileEditScreen>  with TickerProviderStateMixin {


  _updateUserAccount() async {

    if(Get.find<ProfileController>().profileModel!.fName ==  Get.find<ProfileController>().firstNameController.text
        && Get.find<ProfileController>().profileModel!.lName ==  Get.find<ProfileController>().lastNameController.text
        && Get.find<ProfileController>().profileModel!.address ==  Get.find<ProfileController>().addressController.text &&
        Get.find<AuthController>().file == null
        &&  Get.find<ProfileController>().passwordController.text.isEmpty &&
        Get.find<ProfileController>().confirmPasswordController.text.isEmpty) {
      showCustomSnackBarWidget('change_something_to_update'.tr);
    } else if(Get.find<ProfileController>().passwordController.text.isNotEmpty && !Get.find<ProfileController>().isPasswordValid()){
      showCustomSnackBarWidget('enter_valid_password'.tr);
    } else if(Get.find<ProfileController>().passwordController.text.isNotEmpty &&
        Get.find<ProfileController>().passwordController.text.length <8){
      showCustomSnackBarWidget('password_be_at_least_8_character'.tr);
    }else if(Get.find<ProfileController>().passwordController.text.isNotEmpty  &&
        Get.find<ProfileController>().passwordController.text != Get.find<ProfileController>().confirmPasswordController.text){
      showCustomSnackBarWidget('password_not_match'.tr);
    } else {
      UserInfoModel updateUserInfoModel = Get.find<ProfileController>().profileModel!;
      updateUserInfoModel.fName =  Get.find<ProfileController>().firstNameController.text;
      updateUserInfoModel.lName =  Get.find<ProfileController>().lastNameController.text;
      updateUserInfoModel.address =  Get.find<ProfileController>().addressController.text;
      String _password =  Get.find<ProfileController>().passwordController.text;
      Get.find<ProfileController>().updateUserInfo(updateUserInfoModel, _password);

    }
  }

  TabController? _tabController;
  int selectedIndex = 0;

  @override
  void initState() {
    super.initState();
    Get.find<ProfileController>().clearPassword();
    if(Get.find<ProfileController>().showPassView){
      Get.find<ProfileController>().showHidePass(isUpdate: false);
    }
    _tabController = TabController(length: 2, initialIndex: 0, vsync: this);
    _tabController?.addListener((){
      switch (_tabController!.index){
        case 0:
          Get.find<AuthController>().setIndexForTabBar(1, notify: true);
          break;
        case 1:
          Get.find<AuthController>().setIndexForTabBar(0, notify: true);
          break;
      }
    });
  }

  @override
  Widget build(BuildContext context) {

    return Scaffold(
      body: GetBuilder<ProfileController>(
        builder: (profile) {
          int idImageSize = profile.profileModel?.identityImageFullUrl?.length ?? 0;
          if(profile.firstNameController.text.isEmpty ||  profile.lastNameController.text.isEmpty) {
            profile.firstNameController.text = profile.profileModel!.fName!;
            profile.lastNameController.text = profile.profileModel!.lName!;
            profile.addressController.text = profile.profileModel!.address!;
          }
          return ListView(
            children: [

              ProfileHeaderWidget(profileController: profile),

              Center(child: Container(width: MediaQuery.of(context).size.width,
                  color: Get.isDarkMode ? Theme.of(context).cardColor : Theme.of(context).canvasColor,
                  child: TabBar(padding:  EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeExtraLarge),
                    controller: _tabController,
                    labelColor:Get.isDarkMode? Theme.of(context).textTheme.bodyLarge?.color : Theme.of(context).primaryColor,
                    unselectedLabelColor: Theme.of(context).hintColor,
                    indicatorColor: Get.isDarkMode? ColorHelper.blendColors(Colors.white, Theme.of(context).primaryColor, 0.9).withValues(alpha:.8) : Theme.of(context).primaryColor,
                    dividerColor: Colors.transparent,
                    indicatorWeight: 1,
                    onTap: (val){
                      setState(() {
                        selectedIndex  = val;
                      });
                    },
                    unselectedLabelStyle: rubikRegular.copyWith(fontSize: Dimensions.fontSizeDefault, fontWeight: FontWeight.w400),
                    labelStyle: rubikRegular.copyWith(fontSize: Dimensions.fontSizeDefault, fontWeight: FontWeight.w700),
                    tabs: [Tab(text: 'general_info'.tr), Tab(text: 'login_info'.tr)]))),


              SizedBox(height: selectedIndex == 0 ? (idImageSize > 4 ? 950 : idImageSize > 2 ? 815 : 750) : 350,
                child: Padding(padding:  EdgeInsets.symmetric(vertical: Dimensions.paddingSizeDefault,
                    horizontal: Dimensions.paddingSizeDefault),
                  child: TabBarView(physics: const NeverScrollableScrollPhysics(),
                    controller: _tabController, children: const [
                      SizedBox(child: GeneralInfoWidget()),
                      AccountInfoWidget()])),
              ),

              Padding(padding:  EdgeInsets.symmetric(horizontal:Dimensions.paddingSizeDefault),
                child: Container(width: MediaQuery.of(context).size.width,
                  margin:  EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeLarge, vertical: Dimensions.paddingSizeSmall),
                  child: !profile.isLoading ?
                  CustomButtonWidget(onTap: _updateUserAccount, btnTxt: 'update_profile'.tr) :
                  Center(child: CircularProgressIndicator(valueColor: AlwaysStoppedAnimation<Color>(Theme.of(context).primaryColor))),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}
