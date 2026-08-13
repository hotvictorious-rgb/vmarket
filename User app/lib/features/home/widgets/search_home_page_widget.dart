import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/localization/controllers/localization_controller.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/theme/controllers/theme_controller.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:provider/provider.dart';

class SearchHomePageWidget extends StatelessWidget {
  const SearchHomePageWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeExtraExtraSmall),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: Dimensions.homePagePadding, vertical: Dimensions.paddingSizeSmall),
        alignment: Alignment.center,
        child: Container(
          padding: const EdgeInsets.only(left: Dimensions.paddingSizeDefault, right: 6),
          height: 48,
          alignment: Alignment.centerLeft,
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(24),
            border: Border.all(color: const Color(0xFFE0E0E0), width: 1),
            boxShadow: [
              BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: const Offset(0, 2)),
            ],
          ),
          child: Row(
            children: [
              Expanded(
                child: Text(
                  'Search on Victorious MARKET...',
                  style: textRegular.copyWith(
                    color: const Color(0xFF9E9E9E),
                    fontSize: Dimensions.fontSizeDefault,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.all(7),
                decoration: BoxDecoration(
                  color: Theme.of(context).primaryColor,
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.search, color: Colors.white, size: 18),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
