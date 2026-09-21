import 'package:flutter/material.dart';
import 'package:sixvalley_vendor_app/features/order_details/domain/models/order_details_model.dart';
import 'package:sixvalley_vendor_app/features/order/domain/models/order_model.dart';
import 'package:sixvalley_vendor_app/features/product/domain/models/product_model.dart';
import 'package:sixvalley_vendor_app/helper/price_converter.dart';
import 'package:sixvalley_vendor_app/localization/language_constrants.dart';
import 'package:sixvalley_vendor_app/utill/dimensions.dart';
import 'package:sixvalley_vendor_app/utill/styles.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_image_widget.dart';



class OrderedProductListItemWidget extends StatefulWidget {
  final OrderDetailsModel? orderDetailsModel;
  final String? paymentStatus;
  final OrderModel? orderModel;
  final int? orderId;
  final int? index;
  final int? length;
  const OrderedProductListItemWidget({super.key, this.orderDetailsModel, this.paymentStatus, this.orderModel, this.orderId, this.index, this.length});

  @override
  State<OrderedProductListItemWidget> createState() => _OrderedProductListItemWidgetState();
}

class _OrderedProductListItemWidgetState extends State<OrderedProductListItemWidget> {

  Variation? variation;
  @override
  Widget build(BuildContext context) {
    final product = widget.orderDetailsModel?.productDetails;

    if (product?.productType == 'physical' && product?.variation != null && product!.variation!.isNotEmpty) {
      for(Variation v in product.variation ?? []) {
        if(v.type == widget.orderDetailsModel!.variant){
          variation = v;
        }
      }
    }

    if (product == null) return const SizedBox();

    final double discountAmount = product.discount ?? 0;
    final bool hasDiscount = discountAmount > 0;
    final double basePrice = (product.productType == 'physical' && product.variation != null && product.variation!.isNotEmpty)
            ? (variation?.price?.toDouble() ?? 0)
            : (product.unitPrice?.toDouble() ?? 0);

    return Padding(
      padding: const EdgeInsets.only(bottom: Dimensions.paddingSizeExtraSmall),
      child: Container(decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(Dimensions.paddingSizeExtraSmall),
          border: Border.all(width: .5, color: Theme.of(context).primaryColor.withValues(alpha:.125))),
        padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeSmall, vertical:Dimensions.paddingSizeSmall),
        child: Column( children: [
          Row(mainAxisAlignment: MainAxisAlignment.start, children: [

            Stack(children: [
              Container(
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(Dimensions.paddingSizeExtraSmall),
                  border: Border.all(width: .5, color: Theme.of(context).primaryColor.withValues(alpha:.125)),
                ),
                height: Dimensions.imageSize, width: Dimensions.imageSize,
                child: ClipRRect(
                    borderRadius: BorderRadius.circular(10),
                    child: CustomImageWidget(height: Dimensions.imageSize, width: Dimensions.imageSize,
                        image: '${product.thumbnailFullUrl?.path}')
                ),
              ),

              if(discountAmount > 0 || product.clearanceSale != null)
                Positioned(top: 10, left: 0, child: Container(height: 20,
                  padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeExtraSmall),
                  decoration: BoxDecoration(color: Theme.of(context).primaryColor,
                    borderRadius: const BorderRadius.only(topRight: Radius.circular(Dimensions.paddingSizeExtraSmall), bottomRight: Radius.circular(Dimensions.paddingSizeExtraSmall)),),

                  child: Center(
                    child: Text(product.clearanceSale != null ?
                    PriceConverter.percentageCalculation(
                      context,
                      product.unitPrice,
                      product.clearanceSale?.discountAmount,
                      product.clearanceSale?.discountType,
                    ) :
                    PriceConverter.percentageCalculation(
                      context,
                      product.unitPrice,
                      product.discount,
                      product.discountType,
                    ), style: titilliumRegular.copyWith(
                      color: Theme.of(context).cardColor,
                      fontSize: Dimensions.fontSizeSmall,
                    )),
                  ),
                )),
            ]),
            const SizedBox(width: Dimensions.paddingSizeDefault),


            Expanded(
              child: Column(crossAxisAlignment:CrossAxisAlignment.start, children: [
                Text(product.name ?? '',
                  style: titilliumSemiBold.copyWith(fontSize: Dimensions.fontSizeDefault,
                      color: Theme.of(context).textTheme.bodyLarge?.color),
                  maxLines: 1, overflow: TextOverflow.ellipsis),



                Row( children: [
                  hasDiscount ?
                  Text(PriceConverter.convertPrice(context, basePrice),
                    style: titilliumRegular.copyWith(color: Theme.of(context).colorScheme.error,fontSize: Dimensions.fontSizeSmall,
                        decoration: TextDecoration.lineThrough),) : const SizedBox(),
                  SizedBox(width: hasDiscount ? Dimensions.paddingSizeDefault : 0),



                  Text(PriceConverter.convertPrice(context,
                      basePrice,
                      discount : product.discount,
                      discountType : product.discountType),
                    style: titilliumSemiBold.copyWith(color: Theme.of(context).primaryColor),),


                ],),

                // Padding(padding: const EdgeInsets.symmetric(vertical: 0.0),
                //     child: widget.orderDetailsModel!.productDetails!.taxModel == 'exclude'?
                //     Text('${getTranslated('tax', context)} ${PriceConverter.convertPrice(context, widget.orderDetailsModel!.tax)}',
                //         style: robotoRegular.copyWith(fontSize: Dimensions.fontSizeSmall, color: Theme.of(context).textTheme.bodyLarge?.color)):
                //     Text('${getTranslated('tax', context)} ${widget.orderDetailsModel!.productDetails!.taxModel}',
                //         style: robotoRegular.copyWith(fontSize: Dimensions.fontSizeSmall, color: Theme.of(context).textTheme.bodyLarge?.color))),

                // const SizedBox(height: Dimensions.paddingSizeExtraSmall),

                (widget.orderDetailsModel!.variant != null && widget.orderDetailsModel!.variant!.isNotEmpty) ?
                Padding(padding: const EdgeInsets.only(top: 0.0),
                  child: Text(widget.orderDetailsModel!.variant!,
                      style: robotoRegular.copyWith(fontSize: Dimensions.fontSizeSmall,
                        color: Theme.of(context).disabledColor,)),) : const SizedBox(),
                // const SizedBox(height: Dimensions.paddingSizeExtraSmall),

                Row(children: [
                  Text(getTranslated('qty', context)!,
                      style: titilliumRegular.copyWith(color: Theme.of(context).hintColor)),

                  Text(': ${widget.orderDetailsModel!.qty}',
                      style: titilliumRegular.copyWith(color: Theme.of(context).textTheme.bodyLarge?.color))]),

              ]),
            ),
          ]),




        ],
        ),
      ),
    );
  }


}




