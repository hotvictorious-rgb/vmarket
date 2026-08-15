
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/features/profile/controllers/profile_controller.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_app_bar_widget.dart';
import 'package:sixvalley_delivery_boy/features/profile/screens/bank_info_edit_screen.dart';



class BankInfoScreen extends StatelessWidget {
  const BankInfoScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppBarWidget(title: 'bank_info'.tr,isBack: true,),
        body: GetBuilder<ProfileController>(

          builder: (profileController) {
            String name = profileController.profileModel!.holderName?? '';
            String bank = profileController.profileModel!.bankName?? '';
            String branch = profileController.profileModel!.branch?? '';
            String accountNo = profileController.profileModel!.accountNo?? '';
            return Column(children: [
                GestureDetector(onTap: ()=> Get.to(()=> const BankInfoEditScreen()),
                  child: Padding(padding:  EdgeInsets.all(Dimensions.paddingSizeDefault),
                    child: Row(mainAxisAlignment: MainAxisAlignment.end, children: [
                      Text('edit_info'.tr, style: rubikMedium.copyWith(fontSize: Dimensions.fontSizeLarge,
                          color: Get.isDarkMode? Theme.of(context).hintColor: Theme.of(context).primaryColor)),
                      Icon(Icons.edit, color: Get.isDarkMode? Theme.of(context).hintColor: Theme.of(context).primaryColor)]))),


                Padding(padding:  EdgeInsets.all(Dimensions.paddingSizeDefault),
                  child: Container(width: Get.width,
                    decoration: BoxDecoration(color: Theme.of(context).primaryColor,
                      borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall)),
                    child: Stack(children: [
                        Positioned(child: Align(alignment: Alignment.centerRight,
                            child: Container(width: Get.width/3, height: 200,
                              decoration: BoxDecoration(color: Theme.of(context).cardColor.withValues(alpha:.05),
                                  borderRadius: const BorderRadius.only(topLeft: Radius.circular(100), bottomLeft: Radius.circular(100) ))))),
                        Positioned(child: Align(alignment: Alignment.centerRight,
                            child: Container(width: Get.width/4, height: 200,
                            decoration: BoxDecoration(color: Theme.of(context).cardColor.withValues(alpha:.05),
                              borderRadius: const BorderRadius.only(topLeft: Radius.circular(100), bottomLeft: Radius.circular(100)))))),

                        Column(children: [
                           SizedBox(height: Dimensions.paddingSizeDefault),
                         Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                            CardItem(title: 'ac_holder',value: name),
                           Padding(padding:  EdgeInsets.only(right: Dimensions.paddingSizeDefault),
                             child: SizedBox(width: 40, child: Image.asset(Images.bankInfo)))
                        ],),
                          Divider(color: Theme.of(context).cardColor.withValues(alpha:.5),thickness: 1.5),

                          CardItem(title: 'bank', value: bank),
                          CardItem(title: 'branch', value: branch),
                          CardItem(title: 'account_no' ,value: accountNo),
                           SizedBox(height: Dimensions.paddingSizeDefault),

                  ])])),
                ),
              ],
            );
          }));
  }
}

class CardItem extends StatelessWidget {
  final String? title;
  final String? value;
  const CardItem({Key? key, this.title, this.value}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Padding(padding:  EdgeInsets.fromLTRB(Dimensions.paddingSizeDefault,
        Dimensions.paddingSizeSmall, Dimensions.paddingSizeDefault, Dimensions.paddingSizeSmall),
      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('${title!.tr} : ', style: rubikRegular.copyWith(color:Get.isDarkMode?
          Theme.of(context).hintColor: Theme.of(context).cardColor)),
          SizedBox(width: Get.width/2.4, child: Text(value!,
              style: rubikRegular.copyWith(color:Get.isDarkMode?
              Theme.of(context).hintColor: Theme.of(context).cardColor))),

        ],
      ),
    );
  }
}
