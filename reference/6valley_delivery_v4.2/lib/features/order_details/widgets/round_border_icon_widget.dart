import 'package:flutter/material.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_asset_image_widget.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';

class RoundBorderIconWidget extends StatelessWidget {
  final String image;
  const RoundBorderIconWidget({super.key, required this.image});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(right: Dimensions.paddingSizeSmall),
      child: Container(
        width: 40,
        height: 40,
        decoration: BoxDecoration(
            color: Theme.of(context).cardColor,
            border: Border.all(color: Theme.of(context).primaryColor.withValues(alpha: 0.1), width: 1),
            borderRadius: BorderRadius.circular(50),
            boxShadow: [BoxShadow(
              blurRadius: 4,
              offset: const Offset(0, 3),
              color: Theme.of(context).hintColor.withValues(alpha: .05),
            )]
        ),
        padding:  EdgeInsets.all(Dimensions.paddingSizeSmall),
        child: CustomAssetImageWidget(image),
      ),
    );
  }
}
