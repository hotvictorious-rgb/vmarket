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
        (cartModel?.quantity == null || (cartModel?.quantity ?? 0) < (cartModel?.productInfo?.minimumOrderQty ?? 1));

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
                blurRadius: 10,
                offset: const Offset(0, 3),
              ),
            ],
          ),
          child: Slidable(
            key: ValueKey(cartModel?.id ?? index),
            endActionPane: ActionPane(
              extentRatio: .25,
              motion: const ScrollMotion(),
              children: [
                SlidableAction(
                  onPressed: (value) {
                    cartProvider.removeFromCartAPI(cartModel?.id, index);
                  },
                  backgroundColor: Theme.of(context).colorScheme.error.withValues(alpha: 0.1),
                  foregroundColor: Theme.of(context).colorScheme.error,
                  icon: CupertinoIcons.delete_solid,
                  label: getTranslated('delete', context),
                  borderRadius: const BorderRadius.horizontal(right: Radius.circular(16)),
                ),
              ],
            ),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 300),
              decoration: BoxDecoration(
                color: (highLightColor != Theme.of(context).cardColor && minOrderQty) ? highLightColor : Theme.of(context).cardColor,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(
                  color: (isValidate && (cartModel?.isChecked ?? false) && minOrderQty)
                      ? Theme.of(context).colorScheme.error
                      : const Color(0xFF5E17EB).withValues(alpha: 0.08),
                  width: 1,
                ),
              ),
              child: Padding(
                padding: const EdgeInsets.all(Dimensions.paddingSizeSmall),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Top Section: Checkbox + Product Image + Details
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Checkbox
                        SizedBox(
                          height: 24,
                          width: 24,
                          child: CustomCheckbox(
                            key: UniqueKey(),
                            visualDensity: VisualDensity.compact,
                            fillColor: WidgetStateProperty.resolveWith<Color>((states) {
                              if (states.contains(WidgetState.selected)) {
                                return const Color(0xFF5E17EB);
                              }
                              return Theme.of(context).cardColor;
                            }),
                            side: WidgetStateBorderSide.resolveWith(
                              (states) => BorderSide(
                                width: 2,
                                color: (cartModel?.isChecked ?? false)
                                    ? const Color(0xFF5E17EB)
                                    : Theme.of(context).hintColor.withValues(alpha: 0.40),
                              ),
                            ),
                            checkColor: Colors.white,
                            value: cartModel!.isChecked ?? false,
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
                        const SizedBox(width: Dimensions.paddingSizeSmall),

                        // Thumbnail
                        _CartProductImageWidget(cartModel: cartModel),
                        const SizedBox(width: Dimensions.paddingSizeSmall),

                        // Details (Title, Variant, Reassurance tag)
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              InkWell(
                                onTap: () {
                                  RouterHelper.getProductDetailsRoute(
                                    action: RouteAction.push,
                                    productId: cartModel?.productId,
                                    slug: cartModel?.slug,
                                  );
                                },
                                child: Text(
                                  cartModel?.name ?? '',
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: textBold.copyWith(
                                    fontSize: Dimensions.fontSizeDefault,
                                    color: const Color(0xFF1B1035),
                                    height: 1.3,
                                  ),
                                ),
                              ),

                              if (cartModel?.variant != null && cartModel!.variant!.isNotEmpty)
                                Padding(
                                  padding: const EdgeInsets.only(top: 4),
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFFF1F5F9),
                                      borderRadius: BorderRadius.circular(6),
                                    ),
                                    child: Text(
                                      cartModel!.variant!,
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: textRegular.copyWith(
                                        fontSize: Dimensions.fontSizeExtraSmall,
                                        color: const Color(0xFF475569),
                                      ),
                                    ),
                                  ),
                                ),

                              const SizedBox(height: 4),
                              Row(
                                children: [
                                  const Icon(Icons.verified_outlined, size: 12, color: Color(0xFF059669)),
                                  const SizedBox(width: 4),
                                  Text(
                                    'Physical In-Shop Inspection',
                                    style: textRegular.copyWith(
                                      fontSize: 10,
                                      color: const Color(0xFF059669),
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: Dimensions.paddingSizeSmall),
                    const Divider(height: 1, color: Color(0xFFF1F5F9)),
                    const SizedBox(height: Dimensions.paddingSizeSmall),

                    // Bottom Row: Price Breakdown on Left, Horizontal Stepper on Right
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      crossAxisAlignment: CrossAxisAlignment.center,
                      children: [
                        // Price section
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            if ((cartModel?.discount ?? 0) > 0)
                              Text(
                                PriceConverter.convertPrice(context, cartModel!.price),
                                style: titleRegular.copyWith(
                                  color: Theme.of(context).hintColor,
                                  decoration: TextDecoration.lineThrough,
                                  fontSize: Dimensions.fontSizeExtraSmall,
                                ),
                              ),
                            Text(
                              PriceConverter.convertPrice(
                                context,
                                cartModel!.price,
                                discount: cartModel!.discount,
                                discountType: 'amount',
                              ),
                              style: textBold.copyWith(
                                color: const Color(0xFF1B1035),
                                fontSize: Dimensions.fontSizeLarge,
                              ),
                            ),
                          ],
                        ),

                        // Horizontal Quantity Controls
                        _HorizontalCartQuantityControlsWidget(cartModel: cartModel, index: index),
                      ],
                    ),

                    // Warnings (Min Order Qty & Stock)
                    if (minOrderQty)
                      Padding(
                        padding: const EdgeInsets.only(top: Dimensions.paddingSizeExtraSmall),
                        child: MinOrderQuantityWidget(
                          minOrderQty: cartModel?.productInfo?.minimumOrderQty ?? 1,
                          currentQty: cartModel?.quantity,
                        ),
                      ),

                    if ((cartModel?.productInfo?.isOutOfStock == true ||
                            (cartModel?.productInfo?.totalCurrentStock != null &&
                                cartModel!.quantity! > cartModel!.productInfo!.totalCurrentStock!)) &&
                        cartModel?.productType == "physical")
                      Padding(
                        padding: const EdgeInsets.only(top: 4),
                        child: Text(
                          "${getTranslated("out_of_stock", context)}",
                          style: textBold.copyWith(
                            fontSize: Dimensions.fontSizeSmall,
                            color: Theme.of(context).colorScheme.error,
                          ),
                        ),
                      ),
                  ],
                ),
              ),
            ),
          ),
        ),
      );
    });
  }
}

class _HorizontalCartQuantityControlsWidget extends StatelessWidget {
  final CartModel? cartModel;
  final int index;

  const _HorizontalCartQuantityControlsWidget({
    required this.cartModel,
    required this.index,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: const Color(0xFFE2E8F0), width: 1.5),
      ),
      padding: const EdgeInsets.symmetric(horizontal: 2, vertical: 2),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Decrement Button
          cartModel?.decrement ?? false
              ? const Padding(
                  padding: EdgeInsets.all(6),
                  child: SizedBox(
                    width: 14,
                    height: 14,
                    child: CircularProgressIndicator(
                      color: Color(0xFF5E17EB),
                      strokeWidth: 2,
                    ),
                  ),
                )
              : CartQuantityButton(
                  isIncrement: false,
                  index: index,
                  quantity: cartModel!.quantity,
                  maxQty: cartModel!.productInfo?.totalCurrentStock,
                  cartModel: cartModel,
                  minimumOrderQuantity: cartModel!.productInfo?.minimumOrderQty ?? 1,
                ),

          // Quantity Number Display
          Container(
            constraints: const BoxConstraints(minWidth: 32),
            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
            alignment: Alignment.center,
            child: Text(
              cartModel!.quantity.toString(),
              style: textBold.copyWith(
                color: const Color(0xFF1B1035),
                fontSize: 13.5,
              ),
            ),
          ),

          // Increment Button
          cartModel?.increment ?? false
              ? const Padding(
                  padding: EdgeInsets.all(6),
                  child: SizedBox(
                    width: 14,
                    height: 14,
                    child: CircularProgressIndicator(
                      color: Color(0xFF5E17EB),
                      strokeWidth: 2,
                    ),
                  ),
                )
              : CartQuantityButton(
                  isIncrement: true,
                  index: index,
                  quantity: cartModel!.quantity,
                  maxQty: cartModel!.productInfo?.totalCurrentStock,
                  cartModel: cartModel,
                  minimumOrderQuantity: cartModel!.productInfo?.minimumOrderQty ?? 1,
                ),
        ],
      ),
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
        RouterHelper.getProductDetailsRoute(
          action: RouteAction.push,
          productId: cartModel?.productId,
          slug: cartModel?.slug,
        );
      },
      child: Stack(
        children: [
          Container(
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: const Color(0xFFE2E8F0),
                width: 1,
              ),
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: CustomImageWidget(
                image: '${cartModel?.productInfo?.thumbnailFullUrl?.path}',
                height: 64,
                width: 64,
              ),
            ),
          ),
          if (cartModel?.isProductAvailable == 0)
            Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(12),
                color: Colors.black.withValues(alpha: 0.55),
              ),
              height: 64,
              width: 64,
              child: Center(
                child: Text(
                  "${getTranslated("not_available", context)}",
                  textAlign: TextAlign.center,
                  style: textMedium.copyWith(color: Colors.white, fontSize: 10),
                ),
              ),
            ),
        ],
      ),
    );
  }
}