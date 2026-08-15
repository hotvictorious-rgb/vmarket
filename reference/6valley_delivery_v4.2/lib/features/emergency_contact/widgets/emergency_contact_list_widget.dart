import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_loader_widget.dart';
import 'package:sixvalley_delivery_boy/features/emergency_contact/controllers/emergency_contruct_controller.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/no_data_screen_widget.dart';
import 'package:sixvalley_delivery_boy/features/emergency_contact/widgets/emergency_contact_card_widget.dart';

class EmergencyContactListViewWidget extends StatelessWidget {
  final EmergencyContactController? emergencyContactController;
   const EmergencyContactListViewWidget({Key? key, this.emergencyContactController}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Column(children: [

      !emergencyContactController!.isLoading ? emergencyContactController!.emergencyContactList!.isNotEmpty?
      ListView.builder(
          itemCount: emergencyContactController!.emergencyContactList!.length,
          physics: const NeverScrollableScrollPhysics(),
          shrinkWrap: true,
          itemBuilder: (context, index){
            return EmergencyContactCardWidget(contactList: emergencyContactController!.emergencyContactList![index],);
          }):const NoDataScreenWidget() : CustomLoaderWidget(height: Get.height-300,)
    ],);
  }
}

