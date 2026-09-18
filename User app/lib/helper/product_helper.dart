import 'package:flutter_sixvalley_ecommerce/features/product_details/domain/models/product_details_model.dart';

class ProductHelper{

  static ({double? end, double? start}) getProductPriceRange(ProductDetailsModel? productDetailsModel){
    // [AI] Variations eliminated across Victorious MARKET. All products have a single fixed unit price.
    double? startingPrice = productDetailsModel?.unitPrice ?? 0.0;
    return (start: startingPrice, end: null);
  }

  static String removeIframe(String htmlString) {
    final regex =  RegExp(
      r'(</span></p>)?(</p>)?<iframe[^>]*src="https:\/\/www\.youtube\.com\/embed\/[^"]*"[^>]*><\/iframe><p[^>]*>(<strong[^>]*>\s*<\/strong>)?<span[^>]*>',
      caseSensitive: false,
      dotAll: true,
    );

    return htmlString.replaceAll(regex, '')
        .replaceAll('&nbsp;', '');
  }
}