import 'package:flutter/material.dart';
import 'package:fluttertoast/fluttertoast.dart';
import 'package:provider/provider.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_button_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_drop_down_item_widget.dart';
import 'package:sixvalley_vendor_app/features/order/domain/models/order_model.dart';
import 'package:sixvalley_vendor_app/features/order_details/controllers/order_details_controller.dart';
import 'package:sixvalley_vendor_app/features/order_details/domain/models/order_setup_model.dart';
import 'package:sixvalley_vendor_app/localization/language_constrants.dart';
import 'package:sixvalley_vendor_app/utill/dimensions.dart';
import 'package:sixvalley_vendor_app/utill/styles.dart';

class OrderSetupBottomSheet extends StatefulWidget {
  final Order? orderModel;
  final BuildContext bottomContext;
  const OrderSetupBottomSheet({super.key, this.orderModel, required this.bottomContext});

  @override
  State<OrderSetupBottomSheet> createState() => _OrderSetupBottomSheetState();
}

class _OrderSetupBottomSheetState extends State<OrderSetupBottomSheet> {
  @override
  void initState() {
    final OrderDetailsController orderDetailsController = Provider.of<OrderDetailsController>(context, listen: false);
    orderDetailsController.initializeOrderSetupModel(order: widget.orderModel);

    super.initState();
  }

  @override
  Widget build(BuildContext context) {
  final double keyBoardHeight = MediaQuery.of(context).viewInsets.bottom;

    return Container(
      padding: const EdgeInsets.all(Dimensions.paddingSizeSmall),
      child: Column(mainAxisSize: MainAxisSize.min, children: [

        InkWell(
          onTap: () => Navigator.pop(context),
          child: Align(
            alignment: Alignment.centerRight,
            child: Icon(Icons.cancel_outlined,
              size: Dimensions.iconSizeMedium,
              color: Theme.of(context).hintColor,
            ),
          ),
        ),

        Text(
          getTranslated('order_setup', context)!,
          style: robotoBold.copyWith(fontSize: Dimensions.fontSizeLarge, color: Theme.of(context).textTheme.bodyLarge?.color),
        ),
        const SizedBox(height: Dimensions.paddingSizeMedium),

         Consumer<OrderDetailsController>(
           builder: (_, orderDetailsController, __) {
              final availableStatuses = _availableStatuses(widget.orderModel?.orderStatus)
                  .where((status) => orderDetailsController.orderStatusList.isEmpty || orderDetailsController.orderStatusList.contains(status))
                  .toList();
             return Flexible(
              child: SingleChildScrollView(
                child: Padding(
                  padding: EdgeInsets.only(bottom: keyBoardHeight),
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start,
                    children: [

                       (['ready_for_pickup', 'out_for_delivery', 'delivered', 'returned', 'failed', 'canceled'].contains(widget.orderModel?.orderStatus)) ?
                      Padding(
                        padding: const EdgeInsets.fromLTRB(
                            Dimensions.paddingSizeDefault,
                            Dimensions.paddingSizeExtraSmall,
                            Dimensions.paddingSizeDefault,
                            Dimensions.paddingSizeSmall,
                        ),
                        child: Container(
                            width: MediaQuery.of(context).size.width,
                            decoration: BoxDecoration(
                                border: Border.all(width: .5,color: Theme.of(context).hintColor.withValues(alpha:.125)),
                                color: Theme.of(context).hintColor.withValues(alpha:.12),
                                borderRadius: BorderRadius.circular(Dimensions.paddingSizeExtraSmall)
                            ),
                            child: Padding(
                              padding: const EdgeInsets.all(Dimensions.paddingSize),
                              child: Text(getTranslated(widget.orderModel!.orderStatus, context) ?? widget.orderModel!.orderStatus!),
                            ),
                        ),
                      ) :
                      CustomDropDownItemWidget(
                        title: 'order_status',
                        widget: DropdownButtonFormField<String>(
                           initialValue: availableStatuses.contains(widget.orderModel?.orderStatus) ? widget.orderModel?.orderStatus : availableStatuses.firstOrNull,
                           isExpanded: true,
                           decoration: const InputDecoration(border: InputBorder.none),
                           iconSize: 24, elevation: 16, style: robotoRegular,
                           onChanged: (value){
                             orderDetailsController.orderSetupModel.orderStatus = value;
                           },
                           items: availableStatuses.map<DropdownMenuItem<String>>((String value) {
                            return DropdownMenuItem<String>(
                              value: value,
                              child: Text(getTranslated(value, context)!,
                                  style: robotoRegular.copyWith(color: Theme.of(context).textTheme.bodyLarge?.color)),
                            );
                          }).toList(),
                        ),
                      ),

                      CustomDropDownItemWidget(
                        title: 'payment_status',
                        widget: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeSmall, vertical: Dimensions.paddingSizeExtraSmall),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                getTranslated(widget.orderModel?.paymentStatus ?? 'unpaid', context)!,
                                style: robotoMedium.copyWith(
                                  color: widget.orderModel?.paymentStatus == 'paid' ? Colors.green : Theme.of(context).colorScheme.error,
                                ),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                decoration: BoxDecoration(
                                  color: (widget.orderModel?.paymentStatus == 'paid' ? Colors.green : Theme.of(context).colorScheme.error).withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: Text(
                                  (widget.orderModel?.paymentStatus ?? 'unpaid').toUpperCase(),
                                  style: robotoRegular.copyWith(
                                    fontSize: Dimensions.fontSizeSmall,
                                    color: widget.orderModel?.paymentStatus == 'paid' ? Colors.green : Theme.of(context).colorScheme.error,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),

                      const SizedBox(height: Dimensions.paddingSizeSmall),

                      Padding(
                        padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                        child: CustomButtonWidget(
                          btnTxt: getTranslated('update', context),
                          backgroundColor: Theme.of(context).primaryColor,
                          borderRadius: 8,
                          onTap: () async {
                            if(_canUpdate(orderDetailsController.orderSetupModel, widget.orderModel)){
                              await orderDetailsController.setUpOrder(orderSetupModel: orderDetailsController.orderSetupModel);
                              if (context.mounted) Navigator.pop(context);
                            } else {
                              showToast(message: getTranslated('there_is_no_change_to_update', context)!);
                            }
                          },
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            );

          }
        ),
      ]),
    );
  }

  void showToast({Color backGroundColor = Colors.red, required String message}) {
    Fluttertoast.showToast(
        msg: message,
        toastLength: Toast.LENGTH_SHORT,
        gravity: ToastGravity.BOTTOM,
        timeInSecForIosWeb: 1,
        backgroundColor: backGroundColor,
        textColor: Colors.white,
        fontSize: Dimensions.fontSizeDefault
    );
  }

  List<String> _availableStatuses(String? currentStatus) {
    return switch (currentStatus) {
      'pending' => const ['pending', 'confirmed', 'canceled'],
      'confirmed' => const ['confirmed', 'processing', 'canceled'],
      'processing' => const ['processing', 'ready_for_pickup', 'canceled'],
      'ready_for_pickup' => const ['ready_for_pickup'],
      _ => const [],
    };
  }

  bool _canUpdate(OrderSetupModel orderSetUpModel, Order? order) {
    if (orderSetUpModel.orderStatus == null || order?.orderStatus == orderSetUpModel.orderStatus) {
      return false;
    }
    return switch (order?.orderStatus) {
      'pending' => const {'confirmed', 'canceled'}.contains(orderSetUpModel.orderStatus),
      'confirmed' => const {'processing', 'canceled'}.contains(orderSetUpModel.orderStatus),
      'processing' => const {'ready_for_pickup', 'canceled'}.contains(orderSetUpModel.orderStatus),
      _ => false,
    };
  }
}
