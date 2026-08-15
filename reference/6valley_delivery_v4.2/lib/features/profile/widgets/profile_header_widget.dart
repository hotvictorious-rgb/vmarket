import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/common/controllers/localization_controller.dart';
import 'package:sixvalley_delivery_boy/features/profile/domain/models/userinfo_model.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:sixvalley_delivery_boy/features/profile/screens/edit_profile_screen.dart';
import 'package:sixvalley_delivery_boy/features/profile/widgets/profile_info_widget.dart';



class ProfileHeaderWidget extends StatelessWidget {
  final UserInfoModel? profileModel;
  const ProfileHeaderWidget({Key? key, this.profileModel}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Container(padding:  EdgeInsets.only(top: Dimensions.paddingSizeLarge,),
      color: Theme.of(context).canvasColor,
      alignment: Alignment.center,
      child:Stack(children: [
          Container(height: 130, width: MediaQuery.of(context).size.width,
            padding:  EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
            margin:  EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeSmall),
            decoration: BoxDecoration(
              color: Theme.of(context).primaryColor,
              borderRadius: BorderRadius.circular(Dimensions.paddingSizeChat),
              boxShadow: [BoxShadow(color: Colors.grey[Get.isDarkMode ? 900 : 200]!,
                  spreadRadius: 0.5, blurRadius: 0.3)]),

          ),
          Container(width: MediaQuery.of(context).size.width/1.3, height: 130,
            decoration: BoxDecoration(color: Theme.of(context).cardColor.withValues(alpha:.10),
                borderRadius: BorderRadius.only(bottomRight: Radius.circular(MediaQuery.of(context).size.width/2.5))
            ),),
          Positioned(child: Align(
            alignment: Alignment.centerLeft,
              child: Padding(
                padding: EdgeInsets.all(Dimensions.paddingSizeDefault),
                child: ProfileInfoWidget(profileModel: profileModel),
              ))),

          Get.find<LocalizationController>().isLtr ?
          Positioned(right: 30, top: 20,
              child: InkWell(
                  onTap: (){
                    Get.to(()=> const ProfileEditScreen());
                  },
                  child: SizedBox(width: 30, child: Image.asset(Images.editIcon)))) :
          Positioned(left: 30,top: 20,
              child: InkWell(
                  onTap: (){
                    Get.to(()=> const ProfileEditScreen());
                  },
                  child: SizedBox(width: 30, child: Image.asset(Images.editIcon))))
        ],
      ),
    );
  }
}


