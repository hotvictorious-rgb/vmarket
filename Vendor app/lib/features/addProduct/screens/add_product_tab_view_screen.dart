import 'package:flutter/material.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_app_bar_widget.dart';
import 'package:sixvalley_vendor_app/features/addProduct/domain/models/add_product_model.dart';
import 'package:sixvalley_vendor_app/features/addProduct/domain/models/edt_product_model.dart';
import 'package:sixvalley_vendor_app/features/addProduct/screens/add_product_screen.dart';
import 'package:sixvalley_vendor_app/features/product/domain/models/product_model.dart';
import 'package:sixvalley_vendor_app/localization/language_constrants.dart';

class AddProductTabView extends StatefulWidget {
  final Product? product;
  final AddProductModel? addProduct;
  final EditProductModel? editProduct;
  final bool fromHome;
  const AddProductTabView({super.key, this.product, this.addProduct, this.editProduct, required this.fromHome});

  @override
  State<AddProductTabView> createState() => _AddProductTabViewState();
}

class _AddProductTabViewState extends State<AddProductTabView> {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppBarWidget(
        centerTitle: false,
        title: widget.product != null ? getTranslated('update_product', context) : getTranslated('add_product', context),
                isFilter: true,
        isAction: true,
        onBackPressed: () {
          Navigator.of(context).pop();
        },
      ),
      body: AddProductScreen(
        product: widget.product,
        addProduct: widget.addProduct,
        editProduct: widget.editProduct,
        fromHome: widget.fromHome,
        onTabChanged: (index) {},
      ),
    );
  }
}