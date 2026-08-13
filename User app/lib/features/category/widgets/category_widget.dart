import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/features/category/domain/models/category_model.dart';
import 'package:flutter_sixvalley_ecommerce/localization/controllers/localization_controller.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_image_widget.dart';
import 'package:provider/provider.dart';
class CategoryWidget extends StatelessWidget {
  final CategoryModel category;
  final int index;
  final int length;
  const CategoryWidget({super.key, required this.category, required this.index, required this.length});

  @override
  Widget build(BuildContext context) {
    int homeLength = length >= 10 ? 10 : length;
    return Padding(padding: EdgeInsets.only(
      left : Provider.of<LocalizationController>(context, listen: false).isLtr ? index == 0 ?  Dimensions.homePagePadding : Dimensions.paddingSizeTwelve : 0,
      right: index+1 == homeLength? Dimensions.paddingSizeSmall :
        Provider.of<LocalizationController>(context, listen: false).isLtr ?
        0 : Dimensions.homePagePadding),

      child: Column( children: [
        Container(
          height: 66,
          width: 66,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: Colors.white,
            border: Border.all(color: Theme.of(context).primaryColor.withValues(alpha: .18), width: 1.5),
            boxShadow: [
              BoxShadow(
                color: Theme.of(context).primaryColor.withValues(alpha: .08),
                blurRadius: 6,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: ClipOval(
            child: Padding(
              padding: const EdgeInsets.all(Dimensions.paddingSizeSmall),
              child: CustomImageWidget(
                image: '${category.imageFullUrl?.path}',
                fit: BoxFit.contain,
              ),
            ),
          ),
        ),

        const SizedBox(height: Dimensions.paddingSizeExtraSmall),
        Center(child: SizedBox(width: 72,
          child: Text(category.name??'', textAlign: TextAlign.center, maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: textRegular.copyWith(fontSize: Dimensions.fontSizeSmall,
                color: Theme.of(context).textTheme.bodyLarge?.color))))]
      ),
    );
  }
}
