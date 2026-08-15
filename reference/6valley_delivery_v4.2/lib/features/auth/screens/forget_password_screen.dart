import 'package:country_code_picker/country_code_picker.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_asset_image_widget.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_text_field_widget.dart';
import 'package:sixvalley_delivery_boy/common/controllers/localization_controller.dart';
import 'package:sixvalley_delivery_boy/features/auth/controllers/auth_controller.dart';
import 'package:sixvalley_delivery_boy/features/splash/controllers/splash_controller.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_button_widget.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_app_bar_widget.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_snackbar_widget.dart';
import 'package:sixvalley_delivery_boy/features/auth/widgets/code_picker_widget.dart';
import 'package:sixvalley_delivery_boy/features/auth/screens/otp_verification_screen.dart';


class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({Key? key}) : super(key: key);

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final TextEditingController _numberController = TextEditingController();
  final FocusNode _numberFocus = FocusNode();
  String? _countryDialCode = '880';

  @override
  void initState() {
    _countryDialCode = CountryCode.fromCountryCode(Get.find<SplashController>().configModel!.countryCode!).dialCode;
    super.initState();
  }


  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const CustomAppBarWidget(title: '', isBack: true,),

      body: GetBuilder<AuthController>(
        builder: (authController) {
          return Padding(padding: EdgeInsets.fromLTRB(Dimensions.paddingSizeDefault,0, Dimensions.paddingSizeDefault,0),
            child: Column(crossAxisAlignment: CrossAxisAlignment.center, mainAxisAlignment: MainAxisAlignment.center, children: [

              const CustomAssetImageWidget(Images.forgetPasswordScreenIcon, width: 130, height: 130),
              SizedBox(height: Dimensions.paddingSizeOverLarge),
              
              Padding(
                padding: EdgeInsets.only(bottom: Dimensions.paddingSizeMin),
                child: Text('forget_password'.tr, style: rubikMedium),
              ),

              Text('enter_phone_number_for_password_reset'.tr, style: rubikRegular.copyWith(
                color: Theme.of(context).hintColor,
                fontSize: Dimensions.fontSizeDefault,
              )),

              const SizedBox(height: Dimensions.chatProfileSize),

              Container(decoration: BoxDecoration(
                border: Border.all(color: Get.isDarkMode ? Theme.of(context).hintColor : Theme.of(context).primaryColor.withValues(alpha:.5)),
                borderRadius: BorderRadius.circular(Dimensions.topSpace),
                color: Get.isDarkMode ? Theme.of(context).primaryColor.withValues(alpha:.02) : Theme.of(context).primaryColor.withValues(alpha:.02),
              ),
                  child: Stack(children: [
                    Container(width: 77, height: 53, decoration: BoxDecoration(
                        color: Theme.of(context).primaryColor.withValues(alpha:.125),
                        borderRadius:Get.find<LocalizationController>().isLtr?
                        BorderRadius.only(topLeft: Radius.circular(Dimensions.topSpace),
                            bottomLeft: Radius.circular(Dimensions.topSpace)):
                        BorderRadius.only(topRight: Radius.circular(Dimensions.topSpace),
                            bottomRight: Radius.circular(Dimensions.topSpace),
                        ),
                    )),


                    Row(children: [
                      SizedBox(width: Dimensions.loginColor,
                          child: Padding(
                            padding: const EdgeInsets.only(top: Dimensions.iconSizeSmall),
                            child: CodePickerWidget(
                                dialogBackgroundColor:  Theme.of(context).cardColor,
                                onChanged: (CountryCode countryCode) {
                                  _countryDialCode = countryCode.dialCode;
                                },
                                initialSelection: _countryDialCode,
                                favorite: [_countryDialCode!],
                                showDropDownButton: true,
                                padding: EdgeInsets.zero,
                                showFlagMain: true,
                                textStyle: TextStyle(color: Theme.of(context).textTheme.displayLarge!.color),
                            ),
                          ),
                      ),

                      Expanded(child: CustomTextFieldWidget(
                        hintText: '123456789',
                        controller: _numberController,
                        focusNode: _numberFocus,
                        noPadding: true,
                        inputType: TextInputType.phone,
                        inputAction: TextInputAction.next,
                      )),
                    ]),
                  ]),
              ),


              SizedBox(height: Dimensions.paddingSizeOverLarge),

              !authController.isLoading?
              SizedBox(
                height: 55,
                child: CustomButtonWidget(
                  btnTxt: 'send_otp'.tr,
                  onTap: () {
                    String? code;
                    if(_numberController.text.isEmpty) {
                      showCustomSnackBarWidget('phone_number_is_required'.tr);
                    }else{
                      if(_countryDialCode!.contains('+')){
                        code = _countryDialCode!.replaceAll('+', '');
                      }
                      authController.forgotPassword( code,_numberController.text.trim()).then((value) {
                        if(value.statusCode == 200) {
                          Get.to(VerificationScreen(mobileNumber : _numberController.text.trim(), countryCode: code));
                        }
                      });
                    }
                  },
                ),
              ) :
              Padding(padding: EdgeInsets.symmetric(horizontal: (Get.width/2)-40),
                  child: const SizedBox(width: 40,height: 40,
                      child: CircularProgressIndicator(),
                  ),
              ),
            ]),
          );
        }
      ),
    );
  }
}

