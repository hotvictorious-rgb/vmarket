import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/models/order_model.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_app_bar_widget.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_loader_widget.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/no_data_screen_widget.dart';
import 'package:sixvalley_delivery_boy/features/review/controllers/revice_controller.dart';
import 'package:sixvalley_delivery_boy/features/review/widgets/review_filter_widget.dart';
import 'package:sixvalley_delivery_boy/features/review/widgets/review_list_widget.dart';

class ReviewScreen extends StatefulWidget {
  final OrderModel? orderModel;
  const ReviewScreen({Key? key, this.orderModel}) : super(key: key);

  @override
  State<ReviewScreen> createState() => _ReviewScreenState();
}

class _ReviewScreenState extends State<ReviewScreen> {
  final ScrollController _scrollController = ScrollController();
   String? type;

  @override
  void initState() {
    super.initState();
    Get.find<ReviewController>().getReviewList(1);
    type = Get.find<ReviewController>().reviewTypeList.first;
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<ReviewController>(builder: (reviewController){
      return Scaffold(
        appBar: CustomAppBarWidget(title: 'my_reviews'.tr,isBack: true,),

        body: Column(children: [
          ReviewFilterWidget(
            isBorder: true,
            items: reviewController.reviewTypeList,
            type: type,
            onSelected: (_type){
              type = _type;
              reviewController.update();
              reviewController.setSelectedReviewType = _type;
              Get.find<ReviewController>().getReviewList(1);
            },),

          !reviewController.isLoading && reviewController.reviewModel != null?
          (reviewController.reviewModel!.review != null && reviewController.reviewModel!.review!.isNotEmpty) ?
          Expanded(child: ReviewListViewWidget(reviewController: reviewController, scrollController: _scrollController)) :
          const NoDataScreenWidget(): Expanded(child: CustomLoaderWidget(height: Get.height-300,)),
        ]),
      );
    });
  }
}
