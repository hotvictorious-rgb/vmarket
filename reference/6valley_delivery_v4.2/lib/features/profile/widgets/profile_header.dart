import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/features/auth/controllers/auth_controller.dart';
import 'package:sixvalley_delivery_boy/common/controllers/localization_controller.dart';
import 'package:sixvalley_delivery_boy/features/profile/controllers/profile_controller.dart';
import 'package:sixvalley_delivery_boy/features/profile/widgets/custom_app_bar_edit_profile_widget.dart';
import 'package:sixvalley_delivery_boy/helper/color_helper.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_image_widget.dart';



class ProfileHeaderWidget extends StatelessWidget {
  final ProfileController profileController;
  const ProfileHeaderWidget({Key? key, required this.profileController}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 200,
      child: GetBuilder<ProfileController>(
        builder: (profile) {
          return ColoredBox(
            color: Theme.of(context).cardColor,
            child: Stack(clipBehavior: Clip.none, children: [

                Container(height:  200,
                  width: MediaQuery.of(context).size.width,
                  decoration: BoxDecoration(color: Get.isDarkMode ? ColorHelper.blendColors(Colors.white, Theme.of(context).primaryColor, 0.83)  : Theme.of(context).primaryColor,
                    borderRadius:  BorderRadius.only(bottomRight: Radius.circular(Dimensions.paddingSizeExtraLarge,),
                    bottomLeft: Radius.circular(Dimensions.paddingSizeExtraLarge)))),

                Get.find<LocalizationController>().isLtr?
                Align(
                  alignment: Alignment.centerLeft,
                  child: Container(height: 200,
                    width: MediaQuery.of(context).size.width / 1.5,
                    decoration: BoxDecoration(color: Get.isDarkMode ? Theme.of(context).textTheme.bodyLarge?.color?.withValues(alpha: .10) : Theme.of(context).cardColor.withValues(alpha:.10),
                        borderRadius: BorderRadius.only(bottomRight: Radius.circular(MediaQuery.of(context).size.width)),
                    ),
                  ),
                ): Align(
                  alignment: Alignment.centerLeft,
                  child: Container(height: 200,
                    width: MediaQuery.of(context).size.width/1.5,
                    decoration: BoxDecoration(color: Theme.of(context).cardColor.withValues(alpha:.10),
                        borderRadius: BorderRadius.only(bottomRight: Radius.circular(MediaQuery.of(context).size.width), bottomLeft: const Radius.circular(65))),),
                ),
                
                GetBuilder<AuthController>(
                  builder: (authController) {
                    return Positioned(top: Dimensions.paddingSizeExtraLarge, left: 0,right: 0,
                      child: Column(children: [
                        Container(
                          margin:  EdgeInsets.only(top: Dimensions.paddingSizeExtraLarge),
                          alignment: Alignment.center,
                          decoration: BoxDecoration(
                            color: Theme.of(context).highlightColor,
                            border: Border.all(color: Colors.white, width: 3),
                            shape: BoxShape.circle,
                          ),
                          child: ClipRRect(
                            borderRadius: const BorderRadius.all(Radius.circular(50)),
                            child: Stack(
                              clipBehavior: Clip.none,
                              children: [
                                SizedBox(
                                  width: 100,height: 100,
                                  child: ClipRRect(
                                    borderRadius: BorderRadius.circular(50),
                                    child: authController.file == null
                                        ? CustomImageWidget(image: profile.profileModel != null?
                                    profileController.profileModel!.imageFullUrl?.path ?? '' : '')
                                        : Image.file(authController.file!, width: 100, height: 100, fit: BoxFit.fill),
                                  ),
                                ),
                                GetBuilder<AuthController>(
                                  builder: (authController) {
                                    return Positioned(
                                      bottom: 0,
                                      right: 0, top: 0, left: 0,
                                      child: InkWell(
                                        onTap: authController.choose,
                                        child: CircleAvatar(
                                          backgroundColor: Colors.black.withValues(alpha:0.4),
                                          radius: 14,
                                          child: IconButton(
                                            onPressed: authController.choose,
                                            padding: const EdgeInsets.all(0),
                                            icon: const Icon(Icons.camera_enhance, color: Colors.white),
                                          ),
                                        ),
                                      ),
                                    );
                                  }
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ));
                  }
                ),


              const CustomAppBarEditProfileWidget(),
              
              ],
            ),
          );
        }
      ),
    );
  }
}
