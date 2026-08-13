import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_loader_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/domain/models/cart_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/widgets/cart_quantity_button_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/widgets/custom_checkbox_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/product_details/widgets/min_order_quanty_widget.dart';
import 'package:flutter_sixvalley_ecommerce/helper/price_converter.dart';
import 'package:flutter_sixvalley_ecommerce/helper/route_healper.dart';
import 'package:flutter_sixvalley_ecommerce/localization/controllers/localization_controller.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/controllers/cart_controller.dart';
import 'package:flutter_sixvalley_ecommerce/main.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_image_widget.dart';
import 'package:flutter_slidable/flutter_slidable.dart';
import 'package:provider/provider.dart';

class CartWidget extends StatelessWidget {
  final CartModel? cartModel;
  final int index;
  final bool fromCheckout;
  final Color highLightColor;
  final bool isValidate;
  const CartWidget({
    super.key,
    this.cartModel,
    required this.index,
    required this.fromCheckout,
    required this.highLightColor,
    required this.isValidate,
  });

  @override
  Widget build(BuildContext context) {
    bool minOrderQty = (cartModel?.productInfo?.minimumOrderQty ?? 0) > 1 &&
      (cartModel?.quantity == null || (cartModel?.quantity ?? 0)  < (cartModel?.productInfo?.minimumOrderQty ?? 1));
    return Consumer<CartController>(builder: (context, cartProvider, _) {
      return Padding(
        padding: const EdgeInsets.fromLTRB(
          Dimensions.paddingSizeDefault,
          Dimensions.paddingSizeSmall,
          Dimensions.paddingSizeDefault,
          0,
        ),
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            color: Theme.of(context).cardColor,
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.04),
                blurRadius: 8,
                offset: const Offset(0, 3),
              ),
            ],
          ),
          child: Slidable(
            key: const ValueKey(0),
            endActionPane: ActionPane(
              extentRatio: .25,
              motion: const ScrollMotion(),
              children: [
                SlidableAction(
                  onPressed: (value) {
                    cartProvider.removeFromCartAPI(cartModel?.id, index);
                  },
                  backgroundColor: Theme.of(context).colorScheme.error.withValues(alpha: 0.08),
                  foregroundColor: Theme.of(context).colorScheme.error,
                  icon: CupertinoIcons.delete_solid,
                  label: getTranslated('delete', context),
                  borderRadius: const BorderRadius.horizontal(right: Radius.circular(16)),
                ),
              ],
            ),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 500),
              decoration: BoxDecoration(
                color: (highLightColor != Theme.of(context).cardColor && minOrderQty) ? highLightColor : Theme.of(context).cardColor,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(
                  color: (isValidate && (cartModel?.isChecked ?? false) && minOrderQty)
                      ? Theme.of(context).colorScheme.error
                      : const Color(0xFF6A1B9A).withValues(alpha: 0.08),
                  width: 1,
                ),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  // Checkbox
                  Padding(
                    padding: EdgeInsets.only(
                      top: 0,
                      left: Provider.of<LocalizationController>(context, listen: false).isLtr
                        ? Dimensions.paddingSizeSmall
                        : Dimensions.paddingSizeExtraSmall,
                      right: Provider.of<LocalizationController>(context, listen: false).isLtr
                        ? Dimensions.paddingSizeExtraSmall
                        : Dimensions.paddingSizeSmall,
                    ),
                    child: SizedBox(
                      height: Dimensions.paddingSizeLarge,
                      width: Dimensions.paddingSizeLarge,
                      child: CustomCheckbox(
                        key: UniqueKey(),
                        visualDensity: VisualDensity.compact,
                        fillColor:  WidgetStateProperty.resolveWith<Color>((states) {
                          if (states.contains(WidgetState.selected)) {
                            return Theme.of(context).primaryColor;
                          }
                          if (states.contains(WidgetState.disabled)) {
                            return Theme.of(context).cardColor;
                          }
                          return Theme.of(context).cardColor;
                        }),
                        side: WidgetStateBorderSide.resolveWith(
                            (states) => BorderSide(
                            width: 2,
                            color: (cartModel?.isChecked ?? false) ? Theme.of(context).primaryColor : Theme.of(context).hintColor.withValues(alpha: 0.50),
                          ),
                        ),
                        checkColor: Colors.white,
                        value: cartModel!.isChecked!,
                        onChanged: (bool? value) async {
                          showDialog(
                            context: context,
                            builder: (ctx) => const CustomLoaderWidget(),
                          );
                          await cartProvider.addRemoveCartSelectedItem(
                            [cartModel!.id!],
                            cartModel!.isChecked! ? false : true,
                          );
                          Navigator.of(Get.context!).pop();
                        },
                      ),
                    ),
                  ),

                  // Product details and quantity controls
                  Expanded(child: IntrinsicHeight(child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.start,
                    children: [
                      Flexible(child: Column(mainAxisSize: MainAxisSize.min, children: [
                        Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          Padding(
                            padding: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeSmall),
                            child: _CartProductImageWidget(cartModel: cartModel),
                          ),
                          Expanded(child: Padding(
                            padding: const EdgeInsets.symmetric(
                              horizontal: Dimensions.paddingSizeSmall,
                              vertical: Dimensions.paddingSizeSmall,
                            ),
                            child: _CartProductDetailsWidget(cartModel: cartModel),
                          )),
                        ]),

                        MinOrderQuantityWidget(minOrderQty: cartModel?.productInfo?.minimumOrderQty ?? 1, currentQty: cartModel?.quantity),
                        const SizedBox(height: Dimensions.paddingSizeSmall),

                      ])),
                      const SizedBox(width: Dimensions.paddingSizeDefault),

                      _CartQuantityControlsWidget(cartModel: cartModel, index: index),
                    ],
                  ))),
                ],
              ),
            ),
          ),
        ),
      );
    });
  }
}



class _CartQuantityControlsWidget extends StatelessWidget {
  final CartModel? cartModel;
  final int index;
  const _CartQuantityControlsWidget({
    required this.cartModel,
    required this.index,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFF6A1B9A).withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF6A1B9A).withValues(alpha: 0.12)),
      ),
      margin: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeSmall, horizontal: Dimensions.paddingSizeExtraSmall),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 2),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            cartModel?.increment ?? false ? Padding(
              padding: const EdgeInsets.all(4),
              child: SizedBox(
                width: 14, height: 14,
                child: CircularProgressIndicator(
                  color: Theme.of(context).primaryColor,
                  strokeWidth: 2,
                ),
              ),
            ) : CartQuantityButton(
              index: index,
              isIncrement: true,
              quantity: cartModel!.quantity,
              maxQty: cartModel!.productInfo?.totalCurrentStock,
              cartModel: cartModel,
              minimumOrderQuantity: cartModel!.productInfo!.minimumOrderQty,
              digitalProduct: cartModel!.productType == "digital" ? true : false,
            ),

            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 8,
                  vertical: 3,
                ),
                decoration: BoxDecoration(
                  color: Theme.of(context).cardColor,
                  borderRadius: BorderRadius.circular(8),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.04),
                      blurRadius: 3,
                    ),
                  ],
                ),
                child: Text(
                  cartModel!.quantity.toString(),
                  style: textBold.copyWith(color: const Color(0xFF4A148C), fontSize: 13),
                ),
              ),
            ),

            cartModel?.decrement ?? false ? Padding(
              padding: const EdgeInsets.all(4.0),
              child: SizedBox(
                width: 14,
                height: 14,
                child: CircularProgressIndicator(
                  color: Theme.of(context).primaryColor,
                  strokeWidth: 2,
                ),
              ),
            ) : CartQuantityButton(
              isIncrement: false,
              index: index,
              quantity: cartModel!.quantity,
              maxQty: cartModel!.productInfo!.totalCurrentStock,
              cartModel: cartModel,
              minimumOrderQuantity: cartModel!.productInfo!.minimumOrderQty,
              digitalProduct: cartModel!.productType == "digital" ? true : false,
            ),
          ],
        ),
      ),
    );
  }
}


class _CartProductDetailsWidget extends StatelessWidget {
  final CartModel? cartModel;
  const _CartProductDetailsWidget({required this.cartModel});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Row(children: [
          Expanded(
            child: InkWell(
              onTap: () {
                RouterHelper.getProductDetailsRoute(action: RouteAction.push, productId: cartModel?.productId, slug: cartModel?.slug);
              },
              child: Text(
                cartModel!.name!,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: textMedium.copyWith(
                  fontSize: Dimensions.fontSizeDefault,
                  color: (cartModel!.shop != null &&  cartModel?.shop?.vacationStatus != null && cartModel?.shop?.temporaryClose != null && (cartModel!.shop!.temporaryClose! || cartModel!.shop!.vacationStatus!))
                    ? Theme.of(context).hintColor
                    : Theme.of(context).textTheme.titleMedium?.color,
                ),
              ),
            ),
          ),
          const SizedBox(width: Dimensions.paddingSizeSmall),
        ]),
        // const SizedBox(height: Dimensions.paddingSizeSmall),

        Row(
          children: [
            // Price with discount
            cartModel!.discount! > 0 ? Text(
              PriceConverter.convertPrice(context, cartModel!.price),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: titleRegular.copyWith(
                color: Theme.of(context).textTheme.titleMedium?.color,
                decoration: TextDecoration.lineThrough,
                fontSize: Dimensions.fontSizeSmall,
              ),
            ) : const SizedBox(),

            if(cartModel!.discount! > 0)
              SizedBox(width: Dimensions.paddingSizeSmall),

            // Discounted price
            Text(
              PriceConverter.convertPrice(
                context,
                cartModel!.price,
                discount: cartModel!.discount,
                discountType: 'amount',
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: textBold.copyWith(
                color: (cartModel!.shop != null && cartModel?.shop?.vacationStatus != null && cartModel?.shop?.temporaryClose != null && (cartModel!.shop!.temporaryClose! || cartModel!.shop!.vacationStatus!))
                  ? Theme.of(context).hintColor
                  : Theme.of(context).textTheme.bodyMedium?.color,
                fontSize: Dimensions.fontSizeDefault,
              ),
            ),
          ],
        ),



        // Variation
        (cartModel!.variant != null && cartModel!.variant!.isNotEmpty)
            ? Padding(
          padding: const EdgeInsets.only(top: 0),
          child: Row(children: [
            Flexible(child: Text(
              cartModel?.variant ?? '',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: textRegular.copyWith(
                fontSize: Dimensions.fontSizeDefault,
                color: Theme.of(context).textTheme.titleMedium?.color,
              ),
            )),
          ]),
        ) : const SizedBox(),
        const SizedBox(width: Dimensions.paddingSizeSmall),


        /// Todo: remove vat tax
        // Tax info
        // cartModel!.taxModel == 'exclude'
        //     ? Padding(
        //   padding: const EdgeInsets.only(top: Dimensions.paddingSizeExtraSmall),
        //   child: Text(
        //     '(${getTranslated('tax', context)} : ${PriceConverter.convertPrice(context, cartModel?.tax)})',
        //     style: textRegular.copyWith(
        //       color: Theme.of(context).textTheme.titleMedium?.color,
        //       fontSize: Dimensions.fontSizeDefault,
        //     ),
        //   ),
        // )
        //     : Padding(
        //   padding: const EdgeInsets.only(top: Dimensions.paddingSizeExtraSmall),
        //   child: Text(
        //     '(${getTranslated('tax', context)} ${cartModel!.taxModel})',
        //     style: textRegular.copyWith(
        //       color: Theme.of(context).textTheme.titleMedium?.color,
        //       fontSize: Dimensions.fontSizeDefault,
        //     ),
        //   ),
        // ),


        // Shipping cost
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            cartModel!.shippingType != 'order_wise' ? Padding(
              padding: const EdgeInsets.only(top: 0),
              child: Row(
                children: [
                  Text(
                    '${getTranslated('shipping_cost', context)}: ',
                    style: titilliumSemiBold.copyWith(
                      fontSize: Dimensions.fontSizeSmall,
                      color: (cartModel!.shop != null && cartModel?.shop?.temporaryClose != null && cartModel?.shop?.vacationStatus != null &&
                          (cartModel!.shop!.temporaryClose! ||
                              cartModel!.shop!.vacationStatus!))
                          ? Theme.of(context).hintColor
                          : Theme.of(context).textTheme.bodyLarge?.color,
                    ),
                  ),
                  Text(
                    PriceConverter.convertPrice(context, cartModel!.shippingCost),
                    style: textRegular.copyWith(
                      fontSize: Dimensions.fontSizeSmall,
                      color: Theme.of(context).disabledColor,
                    ),
                  ),
                ],
              ),
            ) : const SizedBox(),
          ],
        ),

        // Out of stock warning
        if (cartModel!.quantity! > cartModel!.productInfo!.totalCurrentStock! && cartModel?.productType == "physical")
          Text(
            "${getTranslated("out_of_stock", context)}",
            style: textRegular.copyWith(
              fontSize: Dimensions.fontSizeDefault,
              color: Theme.of(context).colorScheme.error,
            ),
          ),

      ],
    );
  }
}

class _CartProductImageWidget extends StatelessWidget {
  final CartModel? cartModel;
  const _CartProductImageWidget({required this.cartModel});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () {
        RouterHelper.getProductDetailsRoute(action: RouteAction.push, productId: cartModel?.productId, slug: cartModel?.slug);
      },
      child: Stack(
        children: [
          Container(
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(Dimensions.paddingSizeExtraSmall),
              border: Border.all(
                color: Theme.of(context).primaryColor.withValues(alpha: 0.10),
                width: 0.5,
              ),
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(Dimensions.paddingSizeExtraSmall),
              child: CustomImageWidget(
                image: '${cartModel?.productInfo?.thumbnailFullUrl?.path}',
                height: 60,
                width: 60,
              ),
            ),
          ),
          if (cartModel!.isProductAvailable! == 0)
            Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(Dimensions.paddingSizeExtraSmall),
                color: Colors.black.withValues(alpha: 0.5),
              ),
              height: 70,
              width: 70,
              child: Center(
                child: Text(
                  "${getTranslated("not_available", context)}",
                  textAlign: TextAlign.center,
                  style: textMedium.copyWith(color: Colors.white),
                ),
              ),
            )
        ],
      ),
    );
  }
}