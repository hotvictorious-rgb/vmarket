import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shimmer/shimmer.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_snackbar_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/textfeild/custom_text_feild_widget.dart';
import 'package:sixvalley_vendor_app/features/splash/controllers/splash_controller.dart';
import 'package:sixvalley_vendor_app/localization/language_constrants.dart';
import 'package:sixvalley_vendor_app/features/addProduct/controllers/add_product_controller.dart';
import 'package:sixvalley_vendor_app/utill/dimensions.dart';
import 'package:sixvalley_vendor_app/utill/styles.dart';
class TitleAndDescriptionWidget extends StatefulWidget {
  final AddProductController resProvider;
  final int index;
  final String langCode;
  const TitleAndDescriptionWidget({super.key, required this.resProvider, required  this.index, required  this.langCode});

  @override
  State<TitleAndDescriptionWidget> createState() => _TitleAndDescriptionWidgetState();
}

class _TitleAndDescriptionWidgetState extends State<TitleAndDescriptionWidget> {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal : Dimensions.iconSizeSmall),
      // color: Colors.red,
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          // Padding(
          //   padding: const EdgeInsets.symmetric(horizontal: 2),
          //   child: Text('${getTranslated('inset_lang_wise_title_des', context)}',
          //     style: robotoRegular.copyWith(color: Theme.of(context).hintColor,
          //       fontSize: Dimensions.fontSizeSmall),),
          // ),
          // const SizedBox(height: Dimensions.paddingSizeSmall,),

              return Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  InkWell(
                    onTap: () {
                      if(widget.resProvider.titleControllerList[widget.index].text.isEmpty) {
                        showCustomSnackBarWidget('${getTranslated('product_name_required', context)}', context);
                      }else{
                          title: widget.resProvider.titleControllerList[widget.index].text.trim(),
                          langCode: widget.langCode,
                        ).then((value) {
                            setState(() {});
                        });
                    },
                      baseColor: Theme.of(context).primaryColor,
                      highlightColor: Colors.grey[100]!,
                      child: Row(children: [
                        const SizedBox(width: Dimensions.paddingSizeExtraSmall),

                      ]),
                  ),
                ],
              );
            }
          ),


          const SizedBox(height: Dimensions.paddingSizeSmall),

          CustomTextFieldWidget(
            formProduct: true,
            textInputAction: TextInputAction.next,
            controller: widget.resProvider.titleControllerList[widget.index],
            textInputType: TextInputType.name,
            required: true,
            hintText: getTranslated('product_name', context),
            border: true,
            borderColor: Theme.of(context).primaryColor.withValues(alpha: .25),
          ),
          const SizedBox(height: Dimensions.paddingSizeSmall),


          // Row(
          //   children: [
          //     Text(getTranslated('product_description',context)!,
          //       style: robotoRegular.copyWith(color:  ColorResources.titleColor(context),
          //           fontSize: Dimensions.fontSizeDefault),),
          //
          //     Text('*',style: robotoBold.copyWith(color: ColorResources.mainCardFourColor(context),
          //         fontSize: Dimensions.fontSizeDefault),),
          //   ],
          // ),

              return Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  InkWell(
                    onTap: () {
                      if(widget.resProvider.titleControllerList[widget.index].text.isEmpty) {
                        showCustomSnackBarWidget('${getTranslated('product_name_required', context)}', context);
                      }else{
                          title: widget.resProvider.titleControllerList[widget.index].text.trim(),
                          langCode: widget.langCode,
                        ).then((value) {
                            setState(() {});
                        });
                    },
                      baseColor: Theme.of(context).primaryColor,
                      highlightColor: Colors.grey[100]!,
                      child: Row(children: [
                        const SizedBox(width: Dimensions.paddingSizeExtraSmall),

                      ]),
                  ),
                ],
              );
            }
          ),
          const SizedBox(height: Dimensions.paddingSizeSmall),

          CustomTextFieldWidget(
            formProduct: true,
            required: true,
            isDescription: true,
            controller: widget.resProvider.descriptionControllerList[widget.index],
            textInputType: TextInputType.multiline,
            maxLine: 3,
            border: true,
            borderColor: Theme.of(context).primaryColor.withValues(alpha: .25),
            hintText: getTranslated('product_description', context),
          ),
          const SizedBox(height: Dimensions.paddingSizeSmall,),

        ],
      ),
    );
  }
}
