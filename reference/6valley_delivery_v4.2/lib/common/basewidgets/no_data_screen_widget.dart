import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_asset_image_widget.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';


class NoDataScreenWidget extends StatelessWidget {
  final String? noDataImage;
  final String? noDataMessage;
  const NoDataScreenWidget({Key? key, this.noDataImage, this.noDataMessage}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Padding(padding:  EdgeInsets.all(Dimensions.paddingSizeLarge),
      child: Center(child: Column(mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.center, children: [
            CustomAssetImageWidget(noDataImage ?? Images.noDataFound, width: noDataImage != null ? 50 : 150, height: noDataImage != null ? 50 : 150),

            Padding(
              padding: EdgeInsets.only(top: Dimensions.paddingSizeSmall),
              child: Text(noDataMessage ?? 'no_data_found'.tr,
              style: rubikRegular.copyWith(color: Get.isDarkMode ? Theme.of(context).primaryColorLight : noDataMessage != null ? Theme.of(context).hintColor : Theme.of(context).primaryColor,
                  fontSize: noDataMessage != null ? MediaQuery.of(context).size.height * 0.019 : MediaQuery.of(context).size.height * 0.023),
              textAlign: TextAlign.center),
            ),
        ]),
      ),
    );
  }
}
