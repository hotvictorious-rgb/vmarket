import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_text_field_widget.dart';
import 'package:sixvalley_delivery_boy/features/auth/widgets/pass_view.dart';
import 'package:sixvalley_delivery_boy/features/profile/controllers/profile_controller.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_button_widget.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_app_bar_widget.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_snackbar_widget.dart';
import 'package:sixvalley_delivery_boy/features/auth/screens/login_screen.dart';

class ResetPasswordWidget extends StatefulWidget {
  final String? mobileNumber;

  const ResetPasswordWidget({Key? key,required this.mobileNumber}) : super(key: key);

  @override
  _ResetPasswordWidgetState createState() => _ResetPasswordWidgetState();
}

class _ResetPasswordWidgetState extends State<ResetPasswordWidget> {
  TextEditingController? _passwordController;
  TextEditingController? _confirmPasswordController;
  final FocusNode _newPasswordNode = FocusNode();
  final FocusNode _confirmPasswordNode = FocusNode();
  GlobalKey<FormState>? _formKeyReset;

  @override
  void initState() {
    _passwordController = TextEditingController();
    _passwordController?.text = '';
    _confirmPasswordController = TextEditingController();
    Get.find<ProfileController>().validPassCheck('', isUpdate: false);
    super.initState();
  }


  void resetPassword() async {
      String _password = _passwordController!.text.trim();
      String _confirmPassword = _confirmPasswordController!.text.trim();

      if (_password.isEmpty) {
     showCustomSnackBarWidget('password_is_required'.tr);
      } else if (_confirmPassword.isEmpty) {
       showCustomSnackBarWidget('confirm_password_is_required'.tr);
      }
      else if (_password.length < 8) {
        showCustomSnackBarWidget('password_at_least_8_character'.tr);
      }
      else if (_password != _confirmPassword) {
       showCustomSnackBarWidget('password_not_match'.tr);
      } else if(!Get.find<ProfileController>().isPasswordValid()){
        showCustomSnackBarWidget('enter_valid_password'.tr);
      } else {
        Get.find<ProfileController>().resetPassword(widget.mobileNumber,
            _password, _confirmPassword).then((value) {
          if(value.statusCode == 200) {
            Get.to(const LoginScreen());
          }
        });
      }
  }



  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppBarWidget(title: 'reset_password'.tr,isBack: true),

      body: Form(key: _formKeyReset,
        child: GetBuilder<ProfileController>(
          builder: (profileController) {
            return Padding(padding:  EdgeInsets.fromLTRB(Dimensions.paddingSizeDefault, 0, Dimensions.paddingSizeDefault, 0),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisAlignment: MainAxisAlignment.center, children: [
                  Padding(padding:  EdgeInsets.symmetric(vertical: Dimensions.paddingSizeDefault),
                    child: Text('new_password'.tr, style: rubikMedium.copyWith(fontSize: Dimensions.fontSizeDefault))),
                  Container(margin: EdgeInsets.only(bottom: Dimensions.paddingSizeSmall),
                      child: CustomTextFieldWidget(
                        isShowBorder: true,
                        hintText: 'new_password'.tr,
                        focusNode: _newPasswordNode,
                        nextFocus: _confirmPasswordNode,
                        isPassword: true,
                        noBg: true,
                        prefixIconUrl: Images.lock,
                        isShowSuffixIcon: true,
                        controller: _passwordController,
                        onChanged: (value){
                          if(value != null && value.isNotEmpty){
                            if(!profileController.showPassView){
                              profileController.showHidePass();
                            }
                            profileController.validPassCheck(value);
                          }else{
                            if(profileController.showPassView){
                              profileController.showHidePass();
                            }
                          }
                        },
                      )),
                      profileController.showPassView ? const PassView() : const SizedBox(),


                  Padding(padding:  EdgeInsets.symmetric(vertical: Dimensions.paddingSizeDefault),
                    child: Text('confirm_password'.tr, style: rubikMedium.copyWith(fontSize: Dimensions.fontSizeDefault))),

                  Container(margin:  EdgeInsets.only(bottom: Dimensions.paddingSizeLarge),
                      child: CustomTextFieldWidget(
                        hintText: 'confirm_password'.tr,
                        isShowBorder: true,
                        inputAction: TextInputAction.done,
                        focusNode: _confirmPasswordNode,
                        isPassword: true,
                        prefixIconUrl: Images.lock,
                        noBg: true,
                        isShowSuffixIcon: true,
                        controller: _confirmPasswordController)),


                  profileController.isLoading ?
                  Padding(padding: EdgeInsets.symmetric(horizontal: (Get.width/2)-40),
                      child: const SizedBox(width: 40,height: 40,
                          child: CircularProgressIndicator())) :
                  CustomButtonWidget(onTap: resetPassword, btnTxt: 'update_password'.tr),
                ],
              ),
            );
          }
        ),
      ),
    );
  }
}
