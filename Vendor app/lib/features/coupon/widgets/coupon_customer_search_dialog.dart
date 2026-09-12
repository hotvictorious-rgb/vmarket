import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_search_field_widget.dart';
import 'package:sixvalley_vendor_app/features/coupon/controllers/coupon_controller.dart';
import 'package:sixvalley_vendor_app/features/coupon/domain/models/customer_model.dart';
import 'package:sixvalley_vendor_app/localization/language_constrants.dart';
import 'package:sixvalley_vendor_app/utill/dimensions.dart';
import 'package:sixvalley_vendor_app/utill/styles.dart';

class CouponCustomerSearchDialog extends StatefulWidget {
  const CouponCustomerSearchDialog({super.key});

  @override
  State<CouponCustomerSearchDialog> createState() => _CouponCustomerSearchDialogState();
}

class _CouponCustomerSearchDialogState extends State<CouponCustomerSearchDialog> {
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    Provider.of<CouponController>(context, listen: false).getCouponCustomerList(context, '');
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      insetPadding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.radiusDefault)),
      child: Container(
        height: 500,
        padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
        child: Column(
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(getTranslated('select_customer', context)!, style: robotoBold.copyWith(fontSize: Dimensions.fontSizeLarge)),
                IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
            const SizedBox(height: Dimensions.paddingSizeSmall),
            CustomSearchFieldWidget(
              controller: _searchController,
              hint: getTranslated('search_customer', context),
              prefix: Icons.search,
              iconPressed: () => () {},
              onSubmit: (text) => () {},
              onChanged: (value) {
                Provider.of<CouponController>(context, listen: false).getCouponCustomerList(context, value);
              },
            ),
            const SizedBox(height: Dimensions.paddingSizeSmall),
            Expanded(
              child: Consumer<CouponController>(
                builder: (context, couponController, _) {
                  if (couponController.isLoading) {
                    return const Center(child: CircularProgressIndicator());
                  }
                  final customers = couponController.couponCustomerList ?? [];
                  if (customers.isEmpty) {
                    return Center(child: Text(getTranslated('no_customer_found', context) ?? 'No customer found'));
                  }
                  return ListView.builder(
                    itemCount: customers.length,
                    itemBuilder: (context, index) {
                      final Customers customer = customers[index];
                      final String name = '${customer.fName ?? ''} ${customer.lName ?? ''}'.trim();
                      return ListTile(
                        title: Text(name.isNotEmpty ? name : (customer.phone ?? ''), style: robotoRegular),
                        subtitle: customer.phone != null && customer.phone!.isNotEmpty ? Text(customer.phone!, style: robotoRegular.copyWith(fontSize: Dimensions.fontSizeSmall, color: Theme.of(context).hintColor)) : null,
                        onTap: () {
                          couponController.setCustomerInfo(customer.id, name, true);
                          couponController.setCouponCustomerIndex(index, customer.id ?? 0, true);
                          couponController.searchCustomerController.text = name;
                          Navigator.pop(context);
                        },
                      );
                    },
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
