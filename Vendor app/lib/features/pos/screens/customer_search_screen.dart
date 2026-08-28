

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:sixvalley_vendor_app/features/pos/controllers/customer_controller.dart';
import 'package:sixvalley_vendor_app/features/pos/domain/models/customer_model.dart';
import 'package:sixvalley_vendor_app/features/pos/widgets/customer_shimmer_widget.dart';
import 'package:sixvalley_vendor_app/localization/language_constrants.dart';
import 'package:sixvalley_vendor_app/features/pos/controllers/cart_controller.dart';
import 'package:sixvalley_vendor_app/utill/dimensions.dart';
import 'package:sixvalley_vendor_app/utill/images.dart';
import 'package:sixvalley_vendor_app/utill/styles.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_app_bar_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_divider_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_search_field_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/no_data_screen.dart';

class CustomerSearchScreen extends StatefulWidget {
  const CustomerSearchScreen({super.key});

  @override
  State<CustomerSearchScreen> createState() => _CustomerSearchScreenState();
}

class _CustomerSearchScreenState extends State<CustomerSearchScreen> {

  @override
  void initState() {
    Provider.of<CustomerController>(context, listen: false).getCustomerList('all');
    super.initState();
  }

  TextEditingController searchController = TextEditingController();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppBarWidget(title: getTranslated('search_customer', context)),
      body: Column(children: [
        SizedBox(height: 85,
          child: Consumer<CustomerController>(
            builder: (context, customerProvider, _) {
              return Container(
                color: Theme.of(context).cardColor,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(Dimensions.paddingSizeDefault, Dimensions.paddingSizeDefault, Dimensions.paddingSizeDefault, Dimensions.paddingSizeDefault),
                  child: CustomSearchFieldWidget(
                    controller: searchController,
                    hint: getTranslated('search', context),
                    prefix: Images.iconsSearch,
                    iconPressed: () => (){},
                    onSubmit: (text) => (){},
                    onChanged: (value){
                      if(value.toString().isNotEmpty){
                        customerProvider.searchCustomer(context,value);
                      } else {
                        customerProvider.searchCustomer(context,'');
                      }
                    },
                  ),
                ),
              );
            }
        ),),

        Expanded(
          child: Consumer<CustomerController>(
            builder: (context, customerProvider, child) {
              List<Customers>? customerList = customerProvider.searchedCustomerList;
              return customerList != null ? customerList.isNotEmpty ?
              ListView.builder(
                  itemCount: customerList.length,
                  physics: const BouncingScrollPhysics(),
                  itemBuilder: (ctx,index){
                    return InkWell(
                      splashColor: Colors.transparent,
                      onTap: (){
                        customerProvider.setCustomerInfo(customerList![index].id, '${customerList[index].fName} ${customerList[index].lName}',
                          customerProvider.searchedCustomerList![index].phone, true, customerProvider.searchedCustomerList![index].walletBalance,
                        );
                        Provider.of<CartController>(context, listen: false).searchCustomerController.text = '${customerList[index].fName} ${customerList[index].lName}';
                        Navigator.pop(context);
                      },
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                        child: Container(
                            padding: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeSmall),
                            child: Column(mainAxisAlignment: MainAxisAlignment.start,crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('${customerList![index].fName} ${customerList[index].lName}',
                                  style: robotoRegular.copyWith(fontSize: Dimensions.fontSizeDefault, color: Theme.of(context).textTheme.bodyLarge?.color)),
                                const SizedBox(height: Dimensions.paddingSizeMedium,),
                                CustomDividerWidget(height: .5,color: Theme.of(context).hintColor),
                              ],
                            )),
                      ),
                    );
                  }) : const NoDataScreen() : const CustomerShimmer();
            }
          ),
        )

      ],)
    );
  }
}
