import 'package:flutter/material.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/paginated_list_view_widget.dart';
import 'package:sixvalley_delivery_boy/features/review/controllers/revice_controller.dart';
import 'package:sixvalley_delivery_boy/features/review/widgets/review_card_widget.dart';

class ReviewListViewWidget extends StatelessWidget {
  final ReviewController? reviewController;
  final ScrollController? scrollController;
  const ReviewListViewWidget({Key? key, this.reviewController, this.scrollController}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      controller: scrollController,
      reverse: false,
      child: PaginatedListViewWidget(
        scrollController: scrollController,
        totalSize: reviewController!.reviewModel?.totalSize,
        offset: reviewController!.reviewModel != null ? int.parse(reviewController!.reviewModel!.offset!) : null,
        onPaginate: (int? offset) async => await reviewController!.getReviewList(offset!, reload: false),
        itemView: ListView.builder(
          physics: const NeverScrollableScrollPhysics(),
          shrinkWrap: true,
          reverse: false,
          itemCount: reviewController!.reviewModel!.review!.length,
          itemBuilder: (context, index)=> ReviewCardWidget(review : reviewController!.reviewModel!.review![index], index: index))));
  }
}
