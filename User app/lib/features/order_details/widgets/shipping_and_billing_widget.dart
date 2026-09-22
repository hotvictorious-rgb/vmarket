import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/controllers/order_details_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/widgets/icon_with_text_row_widget.dart';
import 'package:flutter_sixvalley_ecommerce/helper/route_healper.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/theme/controllers/theme_controller.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/controllers/splash_controller.dart';
import 'package:flutter_sixvalley_ecommerce/utill/images.dart';
import 'package:provider/provider.dart';

class ShippingAndBillingWidget extends StatefulWidget {
  final OrderDetailsController orderProvider;
  const ShippingAndBillingWidget({super.key, required this.orderProvider});

  @override
  State<ShippingAndBillingWidget> createState() => _ShippingAndBillingWidgetState();
}

class _ShippingAndBillingWidgetState extends State<ShippingAndBillingWidget> {
  bool isExpand = false;

  @override
  Widget build(BuildContext context) {
    final bool isPickup = widget.orderProvider.orders?.orderType == 'pickup' || widget.orderProvider.orders?.orderType == 'self_pickup';

    String shopName = 'Victorious Partner Store';
    String shopAddress = 'Store Pickup Location';

    if (widget.orderProvider.orderDetails != null && widget.orderProvider.orderDetails!.isNotEmpty) {
      if (widget.orderProvider.orderDetails![0].order?.sellerIs == 'admin') {
        shopName = Provider.of<SplashController>(context, listen: false).configModel?.inHouseShop?.name ?? 'Victorious Central Store';
        shopAddress = Provider.of<SplashController>(context, listen: false).configModel?.inHouseShop?.address ?? 'Victorious Central Hub, Nigeria';
      } else if (widget.orderProvider.orderDetails![0].seller?.shop != null) {
        shopName = widget.orderProvider.orderDetails![0].seller?.shop?.name ?? 'Vendor Store';
        shopAddress = widget.orderProvider.orderDetails![0].seller?.shop?.address ?? 'Store Location';
      }
    }

    return  widget.orderProvider.orders?.orderType == 'POS' ? const SizedBox() :
    CollapsibleAddressSection(
      addressContent: Column(
        children: [

          Container(
            color: Theme.of(context).cardColor,
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              isPickup ?
              Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(children: [
                  Icon(Icons.storefront_rounded, color: Theme.of(context).primaryColor, size: 20),
                  const SizedBox(width: 8),
                  Text(getTranslated('store_pickup_location', context) ?? 'Store Pickup Location',
                    style: titilliumSemiBold.copyWith(fontSize: Dimensions.fontSizeDefault, color: Theme.of(context).textTheme.bodyLarge?.color)),
                ]),
                const SizedBox(height: Dimensions.marginSizeSmall),
                IconWithTextRowWidget(
                  isBold: true,
                  icon: Icons.store,
                  text: shopName,
                ),
                const SizedBox(height: Dimensions.marginSizeSmall),
                IconWithTextRowWidget(
                  icon: Icons.location_on,
                  text: shopAddress,
                ),
                const SizedBox(height: Dimensions.marginSizeSmall),
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: Theme.of(context).primaryColor.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: Theme.of(context).primaryColor.withValues(alpha: 0.2)),
                  ),
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Row(
                      children: [
                        Icon(Icons.directions_outlined, color: Theme.of(context).primaryColor, size: 18),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            getTranslated('pickup_direction_guidance', context) ?? 'Need help finding this store? Please message Customer Support for step-by-step guidance.',
                            style: titilliumRegular.copyWith(fontSize: Dimensions.fontSizeSmall, color: Theme.of(context).textTheme.bodyMedium?.color),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    InkWell(
                      onTap: () => RouterHelper.getSupportTicketRoute(action: RouteAction.push),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          color: Theme.of(context).primaryColor,
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.chat_bubble_outline, color: Colors.white, size: 14),
                            const SizedBox(width: 6),
                            Text(
                              getTranslated('message_support', context) ?? 'Message Support for Guidance',
                              style: titilliumSemiBold.copyWith(fontSize: Dimensions.fontSizeExtraSmall, color: Colors.white),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ]),
                ),
                const SizedBox(height: Dimensions.marginSizeSmall),
              ]) :
              (widget.orderProvider.orders!.shippingAddressData != null ?
              Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(getTranslated('shipping_address', context)!,
                  style: titilliumSemiBold.copyWith(fontSize: Dimensions.fontSizeDefault, color: Theme.of(context).textTheme.bodyLarge?.color)
                ),
                const SizedBox(height: Dimensions.marginSizeSmall),

                Row(
                  children: [
                    Expanded(
                      child: IconWithTextRowWidget(
                        isBold: true,
                        icon: Icons.person,
                        text: '${widget.orderProvider.orders!.shippingAddressData != null ?
                        widget.orderProvider.orders!.shippingAddressData!.contactPersonName : ''}',
                      ),
                    ),

                    Expanded(
                      child: IconWithTextRowWidget(
                        icon: Icons.call,
                        text: '${widget.orderProvider.orders!.shippingAddressData != null ?
                        widget.orderProvider.orders!.shippingAddressData!.phone : ''}',),
                    ),
                  ],
                ),
                const SizedBox(height: Dimensions.marginSizeSmall),

                Row(children: [
                  Expanded(child: IconWithTextRowWidget(
                    imageIcon: Images.homeIconAddress,
                    text: '${widget.orderProvider.orders!.shippingAddressData?.addressType != null ?
                    widget.orderProvider.orders!.shippingAddressData!.addressType : ''}', icon: null,)),

                  Expanded(child: IconWithTextRowWidget(
                    imageIcon: Images.countryIconAddress,
                    text: widget.orderProvider.orders!.shippingAddressData?.country ?? '', icon: null,))]
                ),
                if(widget.orderProvider.orders!.shippingAddressData?.country != null || widget.orderProvider.orders!.shippingAddressData?.addressType != null )
                  const SizedBox(height: Dimensions.marginSizeSmall),


                Row(children: [
                  Expanded(child: IconWithTextRowWidget(
                    imageIcon: Images.cityIconAddress,
                    icon: Icons.location_city,
                    text: '${widget.orderProvider.orders!.shippingAddressData?.city != null ?
                    widget.orderProvider.orders!.shippingAddressData!.city : ''}')
                  ),

                  Expanded(child: IconWithTextRowWidget(
                    imageIcon: Images.zipIconAddress,
                    icon: Icons.location_city,
                    text: widget.orderProvider.orders!.shippingAddressData?.zip ?? '')
                  )]
                ),
                if(widget.orderProvider.orders!.shippingAddressData?.city != null || widget.orderProvider.orders!.shippingAddressData?.zip != null )
                  const SizedBox(height: Dimensions.marginSizeSmall),

                Row(mainAxisAlignment:MainAxisAlignment.start, crossAxisAlignment:CrossAxisAlignment.start,
                  children: [
                    Icon(Icons.location_on, color: Provider.of<ThemeController>(context, listen: false).darkTheme?
                    Colors.white : Theme.of(context).hintColor.withValues(alpha: 0.5), size: 25),
                    const SizedBox(width: Dimensions.paddingSizeSmall),

                    Expanded(child: Padding(
                      padding: const EdgeInsets.symmetric(vertical: 1),
                      child: Text('${widget.orderProvider.orders!.shippingAddressData != null ?
                      widget.orderProvider.orders!.shippingAddressData!.address : ''}',
                        maxLines: 3, overflow: TextOverflow.ellipsis,
                        style: titilliumRegular.copyWith(fontSize: Dimensions.fontSizeDefault, color: Theme.of(context).textTheme.bodyLarge?.color))
                    ))
                  ]
                ),
              ],) :
              Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(children: [
                  Icon(Icons.local_shipping_rounded, color: Theme.of(context).primaryColor, size: 20),
                  const SizedBox(width: 8),
                  Text(getTranslated('dispatch_delivery_hub', context) ?? 'Central Logistics Dispatch Hub',
                    style: titilliumSemiBold.copyWith(fontSize: Dimensions.fontSizeDefault, color: Theme.of(context).textTheme.bodyLarge?.color)),
                ]),
                const SizedBox(height: Dimensions.marginSizeSmall),
                IconWithTextRowWidget(
                  isBold: true,
                  icon: Icons.business,
                  text: 'Victorious MARKET Logistics Network',
                ),
                const SizedBox(height: Dimensions.marginSizeSmall),
                IconWithTextRowWidget(
                  icon: Icons.location_on,
                  text: 'Victorious MARKET Central Hub, Nigeria',
                ),
                const SizedBox(height: Dimensions.marginSizeSmall),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                    color: Theme.of(context).primaryColor.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    getTranslated('delivery_handover_notice', context) ?? '🚚 All orders are dispatched via motorized riders directly to your doorstep.',
                    style: titilliumRegular.copyWith(fontSize: Dimensions.fontSizeExtraSmall, color: Theme.of(context).primaryColor),
                  ),
                ),
                const SizedBox(height: Dimensions.marginSizeSmall),
              ])),


              if(widget.orderProvider.orders!.billingAddressData != null &&  widget.orderProvider.orders!.shippingAddressData != null)
                Divider(thickness: .25, color: Theme.of(context).primaryColor.withValues(alpha:0.50)),

              widget.orderProvider.orders!.billingAddressData != null ?
              Padding(
                padding: const EdgeInsets.only(top: Dimensions.paddingSizeSmall),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(getTranslated('billing_address', context)!,
                    style: titilliumSemiBold.copyWith(fontSize: Dimensions.fontSizeDefault, color: Theme.of(context).textTheme.bodyLarge?.color)
                  ),
                  const SizedBox(height: Dimensions.marginSizeSmall),

                  if(widget.orderProvider.orders!.shippingAddressData?.id == widget.orderProvider.orders?.billingAddressData?.id)...[
                    Container(
                      padding: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeExtraSmall),
                      decoration: BoxDecoration(
                        color: Theme.of(context).hintColor.withValues(alpha: 0.10),
                        borderRadius: BorderRadius.circular(Dimensions.radiusSmall)
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.start,
                        children: [
                          const SizedBox(width: Dimensions.paddingSizeSmall),
                          Text(
                            getTranslated('same_as_shipping_address', context)!,
                            style: textMedium.copyWith(fontSize: Dimensions.fontSizeDefault, color: Theme.of(context).textTheme.bodyLarge?.color)
                          )
                        ],
                      ),
                    )
                  ],


                  if(widget.orderProvider.orders!.shippingAddressData?.id != widget.orderProvider.orders?.billingAddressData?.id)...[
                    Row(
                      children: [
                        Expanded(
                          child: IconWithTextRowWidget(
                              isBold: true,
                              icon: Icons.person,
                              text: '${widget.orderProvider.orders!.billingAddressData != null ?
                              widget.orderProvider.orders!.billingAddressData!.contactPersonName : ''}'),
                        ),


                        Expanded(
                          child: IconWithTextRowWidget(icon: Icons.call,
                              text: '${widget.orderProvider.orders!.billingAddressData != null ?
                              widget.orderProvider.orders!.billingAddressData!.phone : ''}'),
                        )
                      ],
                    ),
                    const SizedBox(height: Dimensions.marginSizeSmall),

                    Row(children: [
                      Expanded(child: IconWithTextRowWidget(
                        imageIcon: Images.homeIconAddress,
                        text: '${widget.orderProvider.orders!.billingAddressData?.addressType != null ?
                        widget.orderProvider.orders!.billingAddressData!.addressType : ''}', icon: null,)),

                      Expanded(child: IconWithTextRowWidget(
                        imageIcon: Images.countryIconAddress,
                        text: '${widget.orderProvider.orders!.billingAddressData?.country != null ?
                        widget.orderProvider.orders!.shippingAddressData?.country : ''}', icon: null,))]
                    ),
                    if(widget.orderProvider.orders!.billingAddressData?.country != null || widget.orderProvider.orders!.billingAddressData?.addressType != null )
                      const SizedBox(height: Dimensions.marginSizeSmall),


                    Row(children: [
                      Expanded(child: IconWithTextRowWidget(
                          imageIcon: Images.cityIconAddress,
                          icon: Icons.location_city,
                          text: '${widget.orderProvider.orders!.billingAddressData?.city != null ?
                          widget.orderProvider.orders!.billingAddressData!.city : ''}')),

                      Expanded(child: IconWithTextRowWidget(
                        imageIcon: Images.zipIconAddress,
                        icon: Icons.location_city,
                        text: widget.orderProvider.orders!.billingAddressData?.zip ?? '',))]
                    ),
                    if(widget.orderProvider.orders!.billingAddressData?.city != null || widget.orderProvider.orders!.billingAddressData?.zip != null )
                      const SizedBox(height: Dimensions.marginSizeSmall),

                    Row(mainAxisAlignment:MainAxisAlignment.start,
                      crossAxisAlignment:CrossAxisAlignment.start, children: [
                        Icon(Icons.location_on, color: Provider.of<ThemeController>(context, listen: false).darkTheme?
                        Colors.white : Theme.of(context).hintColor.withValues(alpha: 0.5)),
                        const SizedBox(width: Dimensions.marginSizeSmall),

                        Expanded(child: Padding(
                          padding: const EdgeInsets.symmetric(vertical: 1),
                          child: Text(' ${widget.orderProvider.orders!.billingAddressData != null ?
                          widget.orderProvider.orders!.billingAddressData!.address : ''}',
                              maxLines: 3, overflow: TextOverflow.ellipsis,
                              style: titilliumRegular.copyWith(fontSize: Dimensions.fontSizeDefault, color: Theme.of(context).textTheme.bodyLarge?.color)),
                        )),
                      ],
                    ),
                    const SizedBox(height: Dimensions.paddingSizeSmall),
                  ]


                ]
                ),
              ):const SizedBox(),
            ],
            )
          )



        ],
      ),
    );
  }
}




class CollapsibleAddressSection extends StatefulWidget {
  final Widget addressContent;

  const CollapsibleAddressSection({super.key, required this.addressContent});

  @override
  State<CollapsibleAddressSection> createState() => _CollapsibleAddressSectionState();
}

class _CollapsibleAddressSectionState extends State<CollapsibleAddressSection>
    with SingleTickerProviderStateMixin {
  bool isExpand = true;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        boxShadow: [BoxShadow(color: Theme.of(context).hintColor.withValues(alpha:0.2), spreadRadius:1.5, blurRadius: 3)],
        color: Theme.of(context).cardColor,
      ),

      child: Column(
        children: [
          // Header with toggle icon
          Container(
            color: Theme.of(context).cardColor,
            padding: const EdgeInsets.all(16),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Address',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                    color: Theme.of(context).textTheme.bodyLarge?.color,
                  ),
                ),
                InkWell(
                  onTap: () {
                    setState(() {
                      isExpand = !isExpand;
                    });
                  },
                  child: AnimatedRotation(
                    turns: isExpand ? 0 : 0.5,
                    duration: const Duration(milliseconds: 200),
                    child: const Icon(Icons.keyboard_arrow_down),
                  ),
                ),
              ],
            ),
          ),

          Divider(
            thickness: 0.2,
            height: 1,
            color: Theme.of(context).hintColor.withValues(alpha: 0.45),
          ),

          // Smooth transition for expanded content
          AnimatedSize(
            duration: const Duration(milliseconds: 300),
            curve: Curves.easeInOut,
            alignment: Alignment.topCenter,
            child: isExpand
                ? Container(
              color: Theme.of(context).cardColor,
              padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
              child: widget.addressContent,
            ) : const SizedBox(),
          ),
        ],
      ),
    );
  }
}
