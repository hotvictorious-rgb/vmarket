import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_loader_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/brand/domain/models/brand_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/category/domain/models/category_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/controllers/seller_product_controller.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/features/brand/controllers/brand_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/category/controllers/category_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/search_product/controllers/search_product_controller.dart';
import 'package:flutter_sixvalley_ecommerce/theme/controllers/theme_controller.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:flutter_sixvalley_ecommerce/utill/images.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_button_widget.dart';
import 'package:provider/provider.dart';

class ProductFilterDialog extends StatefulWidget {
  final String? slug;
  final bool fromShop;
  const ProductFilterDialog({super.key, this.slug,  this.fromShop = true});

  @override
  ProductFilterDialogState createState() => ProductFilterDialogState();
}

class ProductFilterDialogState extends State<ProductFilterDialog> {


  @override
  void initState() {
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    final Size size = MediaQuery.sizeOf(context);

    return Dismissible(
      key: const Key('key'),
      direction: DismissDirection.down,
      onDismissed: (_) => Navigator.pop(context),
      child: Consumer<SearchProductController>(builder: (context, searchProvider, child) {
        return Consumer<CategoryController>(builder: (context, categoryProvider,_) {
          return Consumer<BrandController>(builder: (context, brandProvider,_) {
            return Consumer<SellerProductController>(builder: (context, productController,_) {
              return Container(
                constraints: BoxConstraints(maxHeight: size.height * 0.9),
                decoration: BoxDecoration(color: Theme.of(context).highlightColor,
                    borderRadius: const BorderRadius.only(topLeft: Radius.circular(20), topRight: Radius.circular(20))),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [

                    Column( mainAxisSize: MainAxisSize.min, children: [
                      const SizedBox(height: Dimensions.paddingSizeSmall),

                      Center(child: Container(width: 35,height: 4,decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(Dimensions.paddingSizeDefault),
                          color: Theme.of(context).hintColor.withValues(alpha:.5)))),
                      const SizedBox(height: Dimensions.paddingSizeDefault),

                      Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [

                        const SizedBox(width: 64,),

                        Row(
                          mainAxisAlignment: MainAxisAlignment.end,
                          children: [
                            Text(getTranslated('filter', context) ?? '', style: titilliumSemiBold.copyWith(fontSize: Dimensions.fontSizeLarge, color: Theme.of(context).textTheme.bodyLarge?.color)),
                          ],
                        ),


                       (categoryProvider.selectedCategoryIds.isNotEmpty || brandProvider.selectedBrandIds.isNotEmpty
                        ) ? InkWell(
                          onTap: () async {
                            showDialog(context: context, builder: (ctx)  => const CustomLoaderWidget());
                            await categoryProvider.resetChecked(widget.fromShop ? widget.slug! : null, widget.fromShop);
                            searchProvider.setFilterApply(isFiltered: false);
                            categoryProvider.selectedCategoryIds.clear();
                            brandProvider.selectedBrandIds.clear();
                            if(context.mounted) {
                              Provider.of<SearchProductController>(context, listen: false).setProductTypeIndex(0, false);
                              Navigator.of(context).pop();
                            }
                          },
                          child: Row(children: [
                            SizedBox(width: 20, child: Image.asset(Images.reset)),
                            Text('${getTranslated('reset', context)}', style: textRegular.copyWith(color: Theme.of(context).primaryColor)),
                            const SizedBox(width: Dimensions.paddingSizeDefault,)
                          ]),
                        ) : SizedBox(width: size.width * 0.19),

                      ]),

                    ]),

                    Flexible(child: Padding(
                      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
                      child: SingleChildScrollView(
                        padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                          child: Column( crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisSize: MainAxisSize.min,
                            children: [

                              // Category
                             Text(getTranslated('CATEGORY', context) ?? '',
                               style: titilliumSemiBold.copyWith(fontSize: Dimensions.fontSizeLarge, color: Theme.of(context).textTheme.bodyLarge?.color)
                             ),

                             Divider(color: Theme.of(context).hintColor.withValues(alpha:.25), thickness: .5),

                              if(categoryProvider.filteredCategoryList.isNotEmpty)
                                ConstrainedBox(
                                   constraints: const BoxConstraints(
                                    minHeight: 40.0,
                                    maxHeight: 350.0,
                                  ),
                                  child: RepaintBoundary(
                                    child: ListView.builder(
                                      itemCount: categoryProvider.filteredCategoryList.length,
                                      shrinkWrap: true,
                                      itemBuilder: (context, index){
                                        return Column(children: [
                                          CategoryFilterItem(title: categoryProvider.filteredCategoryList[index].name,
                                            checked: categoryProvider.filteredCategoryList[index].isSelected!,
                                            onTap: () => categoryProvider.checkedToggleCategory(index)),
                                          if(categoryProvider.filteredCategoryList[index].isSelected!)
                                            Padding(padding: const EdgeInsets.only(left: Dimensions.paddingSizeExtraLarge),
                                              child: RepaintBoundary(
                                                child: ListView.builder(
                                                  itemCount: categoryProvider.filteredCategoryList[index].subCategories?.length??0,
                                                  shrinkWrap: true,
                                                  padding: EdgeInsets.zero,
                                                  physics: const NeverScrollableScrollPhysics(),
                                                  itemBuilder: (context, subIndex){
                                                    return CategoryFilterItem(title: categoryProvider.filteredCategoryList[index].subCategories![subIndex].name,
                                                      checked: categoryProvider.filteredCategoryList[index].subCategories![subIndex].isSelected!,
                                                      onTap: () => categoryProvider.checkedToggleSubCategory(index, subIndex));
                                                  }),
                                              ),
                                            )
                                        ],
                                        );
                                      }),
                                  ),
                                ),

                              // Brand
                              if(brandProvider.brandList.isNotEmpty)...[
                                Padding(padding: const EdgeInsets.only(top: Dimensions.paddingSizeDefault),
                                  child: Text(getTranslated('brand', context)??'',
                                    style: titilliumSemiBold.copyWith(fontSize: Dimensions.fontSizeLarge, color: Theme.of(context).textTheme.bodyLarge?.color))),

                                Divider(color: Theme.of(context).hintColor.withValues(alpha:.25), thickness: .5),

                                ConstrainedBox(
                                    constraints: const BoxConstraints(
                                      minHeight: 40.0,
                                      maxHeight: 350.0,
                                    ),
                                    child: SizedBox(
                                      child: RepaintBoundary(
                                        child: ListView.builder(
                                          itemCount: brandProvider.brandList.length,
                                          shrinkWrap: true,
                                          itemBuilder: (context, index){
                                            return CategoryFilterItem(title: brandProvider.brandList[index].name,
                                                checked: brandProvider.brandList[index].checked!,
                                                onTap: () => brandProvider.checkedToggleBrand(index));
                                          }),
                                      ),
                                    ),
                                  ),
                              ],
                            ],
                          ),
                        ),
                    )),

                    Padding(padding: const EdgeInsets.all(Dimensions.paddingSizeSmall),
                      child: CustomButton(
                        buttonText: getTranslated('apply', context),
                        onTap: brandProvider.selectedBrandIds.isEmpty && categoryProvider.selectedCategoryIds.isEmpty ? null : () {
                          searchProvider.setFilterApply(isFiltered: true);
                          List<int> selectedBrandIdsList =[];
                          List<int> selectedCategoryIdsList =[];

                          for(CategoryModel category in categoryProvider.filteredCategoryList) {
                            if(category.isSelected!){
                              selectedCategoryIdsList.add(category.id!);
                            }
                          }

                          for(CategoryModel category in categoryProvider.filteredCategoryList) {
                            if(category.isSelected!){
                              if(category.subCategories != null){
                                for(int i=0; i< category.subCategories!.length; i++){
                                  if(category.subCategories?[i].isSelected ?? false){
                                    selectedCategoryIdsList.add(category.subCategories![i].id!);
                                  }
                                }
                              }

                            }
                          }
                          for(BrandModel brand in brandProvider.brandList){
                            if(brand.checked!){
                              selectedBrandIdsList.add(brand.id!);
                            }
                          }

                          String selectedCategoryId = selectedCategoryIdsList.isNotEmpty? jsonEncode(selectedCategoryIdsList) : '[]';
                          String selectedBrandId = selectedBrandIdsList.isNotEmpty? jsonEncode(selectedBrandIdsList) : '[]';

                          if(widget.fromShop) {
                            productController.getSellerProductList(widget.slug.toString(), 1, "", categoryIds: selectedCategoryId,
                              brandIds: selectedBrandId,
                              productType: searchProvider.productTypeIndex == 1 ? 'physical' : 'all'
                            ).then((value) {
                              if(value.response?.statusCode == 200){
                                if(context.mounted) {
                                  Provider.of<SellerProductController>(context, listen: false).setFilterApply(true);
                                  Navigator.pop(context);
                                }
                              }
                            });
                          } else {
                            searchProvider.searchProduct(query : searchProvider.searchController.text.toString(),
                              offset: 1, brandIds: selectedBrandId, categoryIds: selectedCategoryId,
                              sort: searchProvider.sortText, priceMin: searchProvider.minPriceForFilter.toString(), priceMax: searchProvider.maxPriceForFilter.toString());
                            Navigator.pop(context);
                          }
                        },

                      ),
                    ),
                  ],
                ),
              );
            });
          });
        });
      }),
    );
  }
}

class FilterItemWidget extends StatelessWidget {
  final String? title;
  final int index;
  const FilterItemWidget({super.key, required this.title, required this.index});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: Dimensions.paddingSizeSmall),
      child: Container(decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall)),
        child: Row(children: [
          Padding(padding: const EdgeInsets.only(right: Dimensions.paddingSizeSmall),
            child: InkWell(
                onTap: ()=> Provider.of<SearchProductController>(context, listen: false).setFilterIndex(index),
                child: Icon(Provider.of<SearchProductController>(context).filterIndex == index? Icons.check_box_rounded: Icons.check_box_outline_blank_rounded,
                    color: (Provider.of<SearchProductController>(context).filterIndex == index )? Theme.of(context).primaryColor: Theme.of(context).hintColor.withValues(alpha:.5))),
          ),
          Expanded(child: Text(title??'', style: textRegular.copyWith())),

        ],),),
    );
  }
}

class CategoryFilterItem extends StatelessWidget {
  final String? title;
  final bool checked;
  final Function()? onTap;
  const CategoryFilterItem({super.key, required this.title, required this.checked, this.onTap});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: Dimensions.paddingSizeSmall),
      child: InkWell(
        onTap: onTap,
        child: Container(decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall)),
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            Padding(padding: const EdgeInsets.only(right: Dimensions.paddingSizeSmall),
              child: Icon(checked? Icons.check_box_rounded: Icons.check_box_outline_blank_rounded,
                  color: (checked && !Provider.of<ThemeController>(context, listen: false).darkTheme)?
                  Theme.of(context).primaryColor:(checked && Provider.of<ThemeController>(context, listen: false).darkTheme)?
                  Colors.white : Theme.of(context).hintColor.withValues(alpha:.5)),
            ),
            Expanded(child: Text(title??'', style: textRegular.copyWith(color: Theme.of(context).textTheme.bodyLarge?.color))),

          ],),),
      ),
    );
  }
}