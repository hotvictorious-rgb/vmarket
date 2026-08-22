import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/helper/route_healper.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/theme/controllers/theme_controller.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:flutter_widget_from_html_core/flutter_widget_from_html_core.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';


class ProductSpecificationWidget extends StatelessWidget {
  final String productSpecification;
  final Map<String, dynamic>? specifications;

  const ProductSpecificationWidget({super.key, required this.productSpecification, this.specifications});

  @override
  Widget build(BuildContext context) {
    bool hasSpecs = specifications != null && specifications!.isNotEmpty;

    return Column(crossAxisAlignment : CrossAxisAlignment.start, children: [
        Text(getTranslated('product_specification', context)??'', style: textBold.copyWith(
          color: Theme.of(context).textTheme.bodyLarge?.color, fontSize: Dimensions.fontSizeLarge
        )),
        const SizedBox(height: Dimensions.paddingSizeExtraSmall),

        if (hasSpecs)
          Container(
            margin: const EdgeInsets.only(bottom: Dimensions.paddingSizeDefault),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
              border: Border.all(color: Theme.of(context).primaryColor.withValues(alpha: 0.15)),
              color: Theme.of(context).cardColor,
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
              child: Column(
                children: specifications!.entries.map((entry) {
                  final String valueStr = entry.value is List ? (entry.value as List).join(', ') : entry.value.toString();
                  if (valueStr.trim().isEmpty) return const SizedBox();
                  return Container(
                    padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeSmall, vertical: Dimensions.paddingSizeSmall),
                    decoration: BoxDecoration(
                      border: Border(bottom: BorderSide(color: Theme.of(context).hintColor.withValues(alpha: 0.1))),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(
                          flex: 4,
                          child: Text(
                            entry.key,
                            style: textMedium.copyWith(fontSize: Dimensions.fontSizeSmall, color: Theme.of(context).hintColor),
                          ),
                        ),
                        Expanded(
                          flex: 6,
                          child: Text(
                            valueStr,
                            style: textBold.copyWith(fontSize: Dimensions.fontSizeSmall, color: Theme.of(context).textTheme.bodyLarge?.color),
                          ),
                        ),
                      ],
                    ),
                  );
                }).toList(),
              ),
            ),
          ),

        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 0),
          child: Column( mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
            Stack(children: [
              Container(
                height: (productSpecification.isNotEmpty && productSpecification.length > 400) ? 150 : null,
                padding: const EdgeInsets.all(Dimensions.paddingSizeSmall).copyWith(bottom: 0),
                child: SingleChildScrollView(
                  physics: const NeverScrollableScrollPhysics(),
                  child: HtmlWidget(
                    productSpecification,
                    onTapUrl: (String url) {
                      return launchUrl(Uri.parse(url),
                      mode: LaunchMode.externalApplication);
                    },
                    textStyle: TextStyle(color: Theme.of(context).textTheme.bodyLarge?.color, fontSize: Dimensions.fontSizeSmall),
                  ),
                ),
              ),

              if((productSpecification.isNotEmpty && productSpecification.length > 400)) Positioned.fill(child: Align(
                alignment: Alignment.bottomRight,
                child: InkWell(
                onTap: () => RouterHelper.getSpecificationRoute(productSpecification.toString()),
                child: Container(
                  padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeSmall),
                  color: Theme.of(context).cardColor,
                  child: Text(getTranslated('see_more...', context)!, style: titleRegular.copyWith(
                    fontSize: Dimensions.fontSizeDefault,
                    color: Provider.of<ThemeController>(context, listen: false).darkTheme ? Colors.white : Theme.of(context).primaryColor,
                  )),
                ),
                ),
              )),
            ]),


          ]),
        )

      ],
    );
  }
}
