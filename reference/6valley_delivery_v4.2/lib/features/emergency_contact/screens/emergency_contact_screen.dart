import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/features/emergency_contact/controllers/emergency_contruct_controller.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_app_bar_widget.dart';
import 'package:sixvalley_delivery_boy/features/emergency_contact/widgets/emergency_contact_list_widget.dart';

class EmergencyContactScreen extends StatelessWidget {
  const EmergencyContactScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    Get.find<EmergencyContactController>().getEmergencyContactList();
    return Scaffold(
      appBar: CustomAppBarWidget(title: 'emergency_contact'.tr, isBack: true),
      body: RefreshIndicator(
        onRefresh: () async=> Get.find<EmergencyContactController>().getEmergencyContactList(),
        child: CustomScrollView(slivers: [
            SliverToBoxAdapter(child: Column(children:  [
              GetBuilder<EmergencyContactController>(
                builder: (emergencyContactController) =>
                   EmergencyContactListViewWidget(emergencyContactController: emergencyContactController)
                )]))]),
      ),
    );
  }
}
