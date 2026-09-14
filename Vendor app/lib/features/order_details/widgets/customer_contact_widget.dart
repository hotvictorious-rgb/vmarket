import 'package:flutter/material.dart';
import 'package:sixvalley_vendor_app/features/order/domain/models/order_model.dart';
import 'package:sixvalley_vendor_app/helper/color_helper.dart';
import 'package:sixvalley_vendor_app/localization/language_constrants.dart';
import 'package:sixvalley_vendor_app/utill/dimensions.dart';
import 'package:sixvalley_vendor_app/utill/styles.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_image_widget.dart';


class CustomerContactWidget extends StatefulWidget {
  final Order? orderModel;
  const CustomerContactWidget({super.key, this.orderModel});

  @override
  State<CustomerContactWidget> createState() => _CustomerContactWidgetState();
}

class _CustomerContactWidgetState extends State<CustomerContactWidget> {

  @override
  Widget build(BuildContext context) {
    if (widget.orderModel == null) return const SizedBox();

    final bool isGuest = widget.orderModel?.isGuest ?? false;

    final String customerName = isGuest
        ? (widget.orderModel?.shippingAddressData?.contactPersonName ?? widget.orderModel?.billingAddressData?.contactPersonName ?? 'Guest Customer')
        : ('${widget.orderModel?.customer?.fName ?? ''} ${widget.orderModel?.customer?.lName ?? ''}'.trim().isNotEmpty
            ? '${widget.orderModel?.customer?.fName ?? ''} ${widget.orderModel?.customer?.lName ?? ''}'.trim()
            : 'Customer');

    final String customerCity = isGuest
        ? (widget.orderModel?.shippingAddressData != null
            ? '${widget.orderModel?.shippingAddressData?.city ?? ''}, ${widget.orderModel?.shippingAddressData?.country ?? ''}'
            : '${widget.orderModel?.billingAddressData?.city ?? ''}, ${widget.orderModel?.billingAddressData?.country ?? ''}')
        : '';

    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        boxShadow: [BoxShadow(color: Theme.of(context).hintColor.withValues(alpha:0.2), spreadRadius:1.5, blurRadius: 3)],
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault, vertical: Dimensions.paddingSizeSmall),
          child: Row(
            children: [
              isGuest ?
              Text('${getTranslated('customer_info', context) ?? 'Customer Info'} (${getTranslated('guest_customer', context) ?? 'Guest'})',
                  style: robotoBold.copyWith(fontSize: Dimensions.fontSizeLarge, color: Theme.of(context).textTheme.bodyLarge?.color)
              ) :
              Text('${getTranslated('customer_info', context) ?? 'Customer Info'}',
                  style: robotoBold.copyWith(fontSize: Dimensions.fontSizeLarge, color: Theme.of(context).textTheme.bodyLarge?.color)
              ),
            ],
          ),
        ),
        Divider(thickness: 0.2, height: 1, color: Theme.of(context).hintColor.withValues(alpha: .65)),

        Padding(
          padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault, vertical: Dimensions.paddingSizeSmall),
          child: Row(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(50),
                child: CustomImageWidget( height: 50,width: 50, fit: BoxFit.cover,
                image: '${widget.orderModel?.customer?.imageFullPath?.path}')
              ),
              const SizedBox(width: Dimensions.paddingSizeSmall),

              Expanded(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  mainAxisAlignment: MainAxisAlignment.start,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      customerName,
                      style: robotoMedium.copyWith(color: Theme.of(context).textTheme.bodyLarge?.color,
                        fontSize: Dimensions.fontSizeDefault)
                    ),
                    const SizedBox(height: Dimensions.paddingSizeExtraSmall),

                    if (widget.orderModel?.customerId != 0 && customerCity.trim().isNotEmpty)
                    Flexible(
                      child: Text(customerCity,
                       style: titilliumRegular.copyWith(
                         color: ColorHelper.blendColors(Colors.white, Theme.of(context).textTheme.bodyLarge!.color!, 0.7),
                         fontSize: Dimensions.fontSizeDefault),
                       maxLines: 1, overflow: TextOverflow.ellipsis
                      ),
                    ),
                  ],
                ),
              ),


              const SizedBox(width: Dimensions.paddingSizeSmall),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeSmall, vertical: Dimensions.paddingSizeExtraSmall),
                decoration: BoxDecoration(
                  color: Theme.of(context).primaryColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(Dimensions.radiusSmall),
                  border: Border.all(color: Theme.of(context).primaryColor.withValues(alpha: 0.2)),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.shield_outlined, size: 16, color: Theme.of(context).primaryColor),
                    const SizedBox(width: 4),
                    Text(
                      'Protected',
                      style: robotoMedium.copyWith(fontSize: Dimensions.fontSizeSmall, color: Theme.of(context).primaryColor),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),

        Padding(
          padding: const EdgeInsets.fromLTRB(Dimensions.paddingSizeDefault, 0, Dimensions.paddingSizeDefault, Dimensions.paddingSizeSmall),
          child: Text(
            'Delivery managed via Victorious Delivery logistics. Direct contact is restricted for customer privacy.',
            style: titilliumRegular.copyWith(
              fontSize: Dimensions.fontSizeExtraSmall,
              color: Theme.of(context).hintColor,
              fontStyle: FontStyle.italic,
            ),
          ),
        ),

      ]),
    );
  }
}
