import 'dart:io';
import 'dart:math';
import 'package:dotted_border/dotted_border.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shimmer/shimmer.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_asset_image_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_image_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/dropdown_decorator_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/textfeild/custom_text_feild_widget.dart';
import 'package:sixvalley_vendor_app/common/controller/tutorial_controller.dart';
import 'package:sixvalley_vendor_app/features/addProduct/controllers/add_product_image_controller.dart';
import 'package:sixvalley_vendor_app/features/addProduct/controllers/add_product_tax_controller.dart';
import 'package:sixvalley_vendor_app/features/addProduct/controllers/variation_controller.dart';
import 'package:sixvalley_vendor_app/features/addProduct/domain/models/add_product_model.dart';
import 'package:sixvalley_vendor_app/features/addProduct/domain/models/edt_product_model.dart';
import 'package:sixvalley_vendor_app/features/addProduct/domain/models/product_general_info_data_model.dart';
import 'package:sixvalley_vendor_app/features/addProduct/widgets/add_product_section_widget.dart';
import 'package:sixvalley_vendor_app/features/product/controllers/category_controller.dart';
import 'package:sixvalley_vendor_app/features/product/controllers/product_controller.dart';
import 'package:sixvalley_vendor_app/features/product/domain/models/product_model.dart';
import 'package:sixvalley_vendor_app/helper/color_helper.dart';
import 'package:sixvalley_vendor_app/localization/language_constrants.dart';
import 'package:sixvalley_vendor_app/localization/controllers/localization_controller.dart';
import 'package:sixvalley_vendor_app/features/addProduct/controllers/add_product_controller.dart';
import 'package:sixvalley_vendor_app/features/splash/controllers/splash_controller.dart';
import 'package:sixvalley_vendor_app/main.dart';
import 'package:sixvalley_vendor_app/theme/controllers/theme_controller.dart';
import 'package:sixvalley_vendor_app/utill/dimensions.dart';
import 'package:sixvalley_vendor_app/utill/images.dart';
import 'package:sixvalley_vendor_app/utill/styles.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_snackbar_widget.dart';
import 'package:sixvalley_vendor_app/features/addProduct/widgets/select_category_widget.dart';
import 'package:sixvalley_vendor_app/features/addProduct/widgets/title_and_description_widget.dart';
import 'package:sixvalley_vendor_app/features/addProduct/widgets/meta_seo_widget.dart';
import 'package:textfield_tags/textfield_tags.dart';
import 'package:flutter_switch/flutter_switch.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/attribute_view_widget.dart';
import 'package:sixvalley_vendor_app/features/addProduct/widgets/color_variation_image_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/discount_text_field_widget.dart';
import 'package:sixvalley_vendor_app/features/addProduct/domain/models/tax_vat_model.dart';
import 'package:sixvalley_vendor_app/helper/price_converter.dart';
import 'package:image_picker/image_picker.dart';
import 'package:sixvalley_vendor_app/features/shop/controllers/shop_controller.dart';

class AddProductScreen extends StatefulWidget {
  final Product? product;
  final AddProductModel? addProduct;
  final EditProductModel? editProduct;
  final bool fromHome;
  final Function(int) onTabChanged;
  const AddProductScreen(
      {super.key,
      this.product,
      this.addProduct,
      this.editProduct,
      this.fromHome = false,
      required this.onTabChanged});
  @override
  AddProductScreenState createState() => AddProductScreenState();
}

class AddProductScreenState extends State<AddProductScreen>
    with TickerProviderStateMixin, AutomaticKeepAliveClientMixin {
  TabController? _tabController;

  int? length;
  late bool _update;
  int cat = 0, subCat = 0, subSubCat = 0, unit = 0, brand = 0;
  String? unitValue = '';
  List<String> titleList = [];
  List<String> descriptionList = [];
  final ScrollController _scrollController = ScrollController();
  List<int?> brandIds = [];

  final FocusNode _discountNode = FocusNode();
  final FocusNode _shippingCostNode = FocusNode();
  final FocusNode _unitPriceNode = FocusNode();
  final FocusNode _totalQuantityNode = FocusNode();
  final FocusNode _minimumOrderQuantityNode = FocusNode();
  final TextEditingController _discountController = TextEditingController();
  final TextEditingController _taxController = TextEditingController();
  final TextEditingController _colorVariationController =
      TextEditingController();
  List<String> tagList = [];
  TextfieldTagsController? _controller;
  bool _publishToMarketplace = true;

  Future<void> _load() async {
    Provider.of<CategoryController>(context, listen: false).resetCategory();
    String languageCode =
        Provider.of<LocalizationController>(context, listen: false)
                    .locale
                    .countryCode ==
                'US'
            ? 'en'
            : Provider.of<LocalizationController>(context, listen: false)
                .locale
                .countryCode!
                .toLowerCase();
    Provider.of<AddProductTaxController>(Get.context!, listen: false)
        .getTaxVatList();
    await Provider.of<SplashController>(Get.context!, listen: false)
        .getColorList();
    await Provider.of<VariationController>(Get.context!, listen: false)
        .getAttributeList(Get.context!, widget.product, languageCode);
    await Provider.of<CategoryController>(Get.context!, listen: false)
        .getCategoryList(Get.context!, widget.product, languageCode);
    await Provider.of<ProductController>(Get.context!, listen: false)
        .getBrandList(Get.context!, languageCode);
    if (_update && widget.product?.brandId == null) {
      Provider.of<ProductController>(Get.context!, listen: false)
          .setBrandIndex(1, false);
    } else if (!_update) {
      Provider.of<ProductController>(Get.context!, listen: false)
          .setBrandIndex(0, false);
    }
  }

  ProductGeneralInfoData getCurrentFormData() {
    CategoryController categoryController =
        Provider.of<CategoryController>(context, listen: false);
    ProductController productController =
        Provider.of<ProductController>(context, listen: false);
    AddProductController resProvider =
        Provider.of<AddProductController>(context, listen: false);

    AddProductModel? addProductModel = widget.addProduct ?? AddProductModel();
    if (resProvider.videoOptionUpload) {
      addProductModel.productVideo = resProvider.selectedVideoFile;
      addProductModel.videoUrl = '';
    } else {
      addProductModel.productVideo = null;
      addProductModel.videoUrl = resProvider.youtubeLinkController.text.trim();
    }

    return ProductGeneralInfoData(
      categoryId: categoryController.categoryIndex != 0
          ? categoryController
              .categoryList![categoryController.categoryIndex! - 1].id
              .toString()
          : '-1',
      subCategoryId: categoryController.subCategoryIndex != 0
          ? categoryController
              .subCategoryList![categoryController.subCategoryIndex! - 1].id
              .toString()
          : "-1",
      subSubCategoryId: (categoryController.subSubCategoryIndex != 0 &&
              categoryController.subSubCategoryIndex! != -1)
          ? categoryController
              .subSubCategoryList![categoryController.subSubCategoryIndex! - 1]
              .id
              .toString()
          : "-1",
      brandId: Provider.of<SplashController>(Get.context!, listen: false)
                      .configModel!
                      .brandSetting ==
                  "1" &&
              resProvider.productTypeIndex != 1
          ? brandIds[productController.brandIndex!].toString()
          : null,
      unit: (resProvider.unitValue != null && resProvider.unitValue!.isNotEmpty)
          ? resProvider.unitValue
          : unitValue,
      product: widget.product,
      addProduct: addProductModel,
      title: resProvider.titleControllerList[0].text.trim(),
      description: resProvider.descriptionControllerList[0].text.trim(),
    );
  }

  @override
  void initState() {
    super.initState();
    _update = widget.product != null;
    _publishToMarketplace =
        widget.product != null ? (widget.product?.status == 1) : true;

    AddProductController addProductController =
        Provider.of<AddProductController>(context, listen: false);

    Provider.of<AddProductImageController>(context, listen: false)
        .colorImageObject = [];
    Provider.of<AddProductImageController>(context, listen: false)
        .productReturnImageList = [];

    _tabController = TabController(
        length: Provider.of<SplashController>(context, listen: false)
            .configModel!
            .languageList!
            .length,
        initialIndex: 0,
        vsync: this);
    _tabController?.addListener(() {});

    Provider.of<CategoryController>(context, listen: false).removeCategory();

    Provider.of<AddProductController>(context, listen: false)
        .setSelectedPageIndex(0, isUpdate: false);
    _load();
    length = Provider.of<SplashController>(context, listen: false)
        .configModel!
        .languageList!
        .length;
    Provider.of<VariationController>(context, listen: false).initColorCode();
    _taxController.text = '0';
    _discountController.text = '0';
    _controller = TextfieldTagsController();

    if (widget.product != null) {
      unitValue = widget.product!.unit;
      Provider.of<AddProductController>(context, listen: false)
          .productCode
          .text = widget.product!.code ?? '123456';
      Provider.of<AddProductController>(context, listen: false)
          .getEditProduct(context, widget.product!.id);
      Provider.of<AddProductImageController>(context, listen: false)
          .getProductImage(widget.product!.id.toString(), isUpdate: false);
      Provider.of<AddProductController>(context, listen: false)
          .setValueForUnit(widget.product!.unit.toString());
      Provider.of<AddProductController>(context, listen: false)
          .setProductTypeIndex(0, false);

      addProductController.unitPriceController.text =
          PriceConverter.convertPriceWithoutSymbol(
              context, widget.product!.unitPrice);
      _taxController.text = widget.product!.tax.toString();
      Provider.of<VariationController>(context, listen: false)
          .setCurrentStock(widget.product!.currentStock.toString());
      addProductController.shippingCostController.text =
          PriceConverter.convertPriceWithoutSymbol(
              context, widget.product!.shippingCost);
      addProductController.minimumOrderQuantityController.text =
          widget.product!.minimumOrderQty.toString();
      Provider.of<AddProductController>(context, listen: false)
          .setDiscountTypeIndex(
              widget.product!.discountType == 'percent' ? 0 : 1, false);
      _discountController.text = widget.product!.discountType == 'percent'
          ? widget.product!.discount.toString()
          : PriceConverter.convertPriceWithoutSymbol(
              context, widget.product!.discount);
      Provider.of<AddProductController>(context, listen: false).setTaxTypeIndex(
          widget.product!.taxModel == 'include' ? 0 : 1, false);
      Provider.of<AddProductTaxController>(Get.context!, listen: false)
          .setProductVatTax(widget.product?.taxVats);

      if ((widget.product?.variation != null &&
              widget.product!.variation!.isNotEmpty) ||
          (widget.product!.colors != null &&
              widget.product!.colors!.isNotEmpty)) {
        Provider.of<AddProductController>(context, listen: false)
            .setIsAttributeActive(true, notify: false);
      }
      if (widget.product!.tags != null) {
        for (int i = 0; i < widget.product!.tags!.length; i++) {
          tagList.add(widget.product!.tags![i].tag!);
        }
      }
      Provider.of<SplashController>(context, listen: false).getColorList();
      _loadData();
      addProductController.youtubeLinkController.text =
          widget.product?.videoUrl ?? '';
      addProductController.setVideoOption(widget.product?.videoUrl == null ||
          widget.product!.videoUrl!.isEmpty);
      addProductController.removeVideo();
    } else {
      Provider.of<AddProductController>(context, listen: false)
          .productCode
          .text = _generateSKU();
      Provider.of<AddProductController>(context, listen: false)
          .setValueForUnit('select_unit');
      Provider.of<VariationController>(context, listen: false)
          .setCurrentStock('1');
      Provider.of<AddProductController>(context, listen: false)
          .getTitleAndDescriptionList(
              Provider.of<SplashController>(context, listen: false)
                  .configModel!
                  .languageList!,
              null);
      Provider.of<AddProductImageController>(context, listen: false)
          .removeProductImage();
      Provider.of<AddProductController>(context, listen: false)
          .setVideoOption(true);
      Provider.of<AddProductController>(context, listen: false).removeVideo();
    }

    Provider.of<TutorialController>(Get.context!, listen: false)
        .setVisibility(false, isUpdate: false);
  }

  Future<void> _loadData() async {
    String languageCode =
        Provider.of<LocalizationController>(context, listen: false)
                    .locale
                    .countryCode ==
                'US'
            ? 'en'
            : Provider.of<LocalizationController>(context, listen: false)
                .locale
                .countryCode!
                .toLowerCase();

    await Provider.of<VariationController>(context, listen: false)
        .getAttributeList(context, widget.product, languageCode);
    Provider.of<VariationController>(Get.context!, listen: false)
        .generateVariantTypes(Get.context!, widget.product);
    _callGetImages();
  }

  Future<void> _callGetImages() async {
    Future.delayed(const Duration(milliseconds: 800), () async {
      Provider.of<AddProductImageController>(Get.context!, listen: false)
          .getProductImage(widget.product!.id.toString(),
              isStorePreviousImage: true, isUpdate: true);
    });
  }

  @override
  bool get wantKeepAlive => true;

  int addColor = 0;

  @override
  Widget build(BuildContext context) {
    super.build(context);

    double keyboardHeight = MediaQuery.of(context).viewInsets.bottom;

    return Scaffold(
      floatingActionButton: null,
      body: Consumer<VariationController>(
          builder: (context, variationController, child) {
        return Consumer<AddProductController>(
          builder: (context, resProvider, child) {
            return widget.product != null && resProvider.editProduct == null ||
                    variationController.isLoading
                ? const Center(child: CircularProgressIndicator())
                : length != null
                    ? Consumer<SplashController>(
                        builder: (context, splashController, _) {
                        return Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const SizedBox(
                                height: Dimensions.paddingSizeExtraSmall),
                            Expanded(
                              child: Padding(
                                padding: EdgeInsets.only(
                                    bottom: MediaQuery.of(context)
                                        .viewInsets
                                        .bottom),
                                child: SingleChildScrollView(
                                  controller: _scrollController,
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    mainAxisAlignment: MainAxisAlignment.start,
                                    children: [
                                      AddProductSectionWidget(
                                        title: getTranslated(
                                            'basic_info', context)!,
                                        subTitle: getTranslated(
                                            'here_you_can_setup_the_product',
                                            context)!,
                                        childrens: [
                                          Container(
                                            margin: const EdgeInsets.all(
                                                Dimensions.paddingSizeSmall),
                                            decoration: BoxDecoration(
                                              borderRadius:
                                                  BorderRadius.circular(
                                                      Dimensions.radiusDefault),
                                              color: Theme.of(context)
                                                  .primaryColor
                                                  .withValues(alpha: 0.05),
                                            ),
                                            child: Column(
                                              children: [
                                                Padding(
                                                  padding: const EdgeInsets
                                                      .only(
                                                      top: Dimensions
                                                          .paddingSizeSmall),
                                                  child: Padding(
                                                    padding: const EdgeInsets
                                                        .only(
                                                        top: Dimensions
                                                            .paddingSizeMedium,
                                                        left: Dimensions
                                                            .paddingEye,
                                                        bottom: Dimensions
                                                            .paddingEye),
                                                    child: SizedBox(
                                                      width:
                                                          MediaQuery.of(context)
                                                              .size
                                                              .width,
                                                      child: TabBar(
                                                        indicatorSize:
                                                            TabBarIndicatorSize
                                                                .tab,
                                                        tabAlignment:
                                                            TabAlignment.start,
                                                        isScrollable: true,
                                                        dividerColor:
                                                            Theme.of(context)
                                                                .hintColor,
                                                        controller:
                                                            _tabController,
                                                        indicatorColor:
                                                            Theme.of(context)
                                                                .primaryColor,
                                                        indicatorWeight: 12,
                                                        labelColor:
                                                            Theme.of(context)
                                                                .primaryColor,
                                                        indicator:
                                                            UnderlineTabIndicator(
                                                          borderSide: BorderSide(
                                                              width: 2.0,
                                                              color: Theme.of(
                                                                      context)
                                                                  .primaryColor),
                                                          insets: EdgeInsets
                                                              .zero, // no inset — covers full tab
                                                        ),
                                                        indicatorPadding:
                                                            const EdgeInsets
                                                                .symmetric(
                                                                horizontal: 0),
                                                        unselectedLabelStyle:
                                                            robotoRegular
                                                                .copyWith(
                                                          color:
                                                              Theme.of(context)
                                                                  .hintColor,
                                                        ),
                                                        labelStyle:
                                                            robotoBold.copyWith(
                                                          fontSize: Dimensions
                                                              .fontSizeLarge,
                                                          color: Theme.of(
                                                                  context)
                                                              .disabledColor,
                                                        ),
                                                        tabs:
                                                            _generateTabChildren(),
                                                      ),
                                                    ),
                                                  ),
                                                ),
                                                SizedBox(
                                                    height: 235,
                                                    child: AnimatedBuilder(
                                                      animation:
                                                          _tabController!,
                                                      builder:
                                                          (BuildContext context,
                                                              Widget? child) {
                                                        return TabBarView(
                                                            controller:
                                                                _tabController,
                                                            children:
                                                                _generateTabPage(
                                                                    resProvider,
                                                                    _tabController));
                                                      },
                                                    )),
                                              ],
                                            ),
                                          ),
                                          SizedBox(
                                              height:
                                                  Dimensions.paddingSizeLarge),
                                          Padding(
                                            padding: const EdgeInsets.symmetric(
                                                horizontal: Dimensions
                                                    .paddingSizeSmall),
                                            child: Column(
                                              crossAxisAlignment:
                                                  CrossAxisAlignment.start,
                                              children: [
                                                Row(
                                                  children: [
                                                    Text(
                                                        getTranslated(
                                                            'product_images',
                                                            context)!,
                                                        style: robotoBold.copyWith(
                                                            fontSize: Dimensions
                                                                .fontSizeDefault,
                                                            color: Theme.of(
                                                                    context)
                                                                .textTheme
                                                                .bodyLarge
                                                                ?.color)),
                                                    SizedBox(
                                                        width: Dimensions
                                                            .paddingSizeExtraSmall),
                                                    Text('*',
                                                        style: robotoBold.copyWith(
                                                            color: Theme.of(
                                                                    context)
                                                                .colorScheme
                                                                .error,
                                                            fontSize: Dimensions
                                                                .fontSizeDefault)),
                                                  ],
                                                ),
                                                SizedBox(
                                                    height: Dimensions
                                                        .paddingSizeSmall),
                                                Consumer<
                                                    AddProductImageController>(
                                                  builder: (context,
                                                      addProductImageController,
                                                      child) {
                                                    List<dynamic> allPhotos =
                                                        [];
                                                    if (addProductImageController
                                                            .selectedLogoFile !=
                                                        null) {
                                                      allPhotos.add(
                                                          addProductImageController
                                                              .selectedLogoFile);
                                                    } else if (widget
                                                                .product
                                                                ?.thumbnailFullUrl
                                                                ?.path !=
                                                            null &&
                                                        widget
                                                            .product!
                                                            .thumbnailFullUrl!
                                                            .path!
                                                            .isNotEmpty) {
                                                      allPhotos.add(widget
                                                          .product!
                                                          .thumbnailFullUrl!
                                                          .path);
                                                    }

                                                    if (addProductImageController
                                                        .imagesWithoutColor
                                                        .isNotEmpty) {
                                                      allPhotos.addAll(
                                                          addProductImageController
                                                              .imagesWithoutColor);
                                                    }

                                                    if (addProductImageController
                                                        .withoutColor
                                                        .isNotEmpty) {
                                                      allPhotos.addAll(
                                                          addProductImageController
                                                              .withoutColor
                                                              .map((img) =>
                                                                  img.image));
                                                    }

                                                    return GridView.builder(
                                                      shrinkWrap: true,
                                                      physics:
                                                          const NeverScrollableScrollPhysics(),
                                                      gridDelegate:
                                                          const SliverGridDelegateWithFixedCrossAxisCount(
                                                        crossAxisCount: 3,
                                                        crossAxisSpacing: 8,
                                                        mainAxisSpacing: 8,
                                                        childAspectRatio: 1,
                                                      ),
                                                      itemCount:
                                                          allPhotos.length + 1,
                                                      itemBuilder:
                                                          (context, index) {
                                                        if (index <
                                                            allPhotos.length) {
                                                          final photo =
                                                              allPhotos[index];
                                                          final bool isCover =
                                                              index == 0;

                                                          return Stack(
                                                            children: [
                                                              Container(
                                                                decoration:
                                                                    BoxDecoration(
                                                                  borderRadius:
                                                                      BorderRadius.circular(
                                                                          Dimensions
                                                                              .radiusDefault),
                                                                  border: Border.all(
                                                                      color: Theme.of(
                                                                              context)
                                                                          .primaryColor
                                                                          .withOpacity(
                                                                              0.2)),
                                                                ),
                                                                child:
                                                                    ClipRRect(
                                                                  borderRadius:
                                                                      BorderRadius.circular(
                                                                          Dimensions
                                                                              .radiusDefault),
                                                                  child: photo
                                                                          is String
                                                                      ? CustomImageWidget(
                                                                          image:
                                                                              photo,
                                                                          width:
                                                                              double.infinity,
                                                                          height:
                                                                              double.infinity,
                                                                          fit: BoxFit
                                                                              .cover,
                                                                        )
                                                                      : Image
                                                                          .file(
                                                                          File(photo
                                                                              .path),
                                                                          width:
                                                                              double.infinity,
                                                                          height:
                                                                              double.infinity,
                                                                          fit: BoxFit
                                                                              .cover,
                                                                        ),
                                                                ),
                                                              ),
                                                              if (isCover)
                                                                Positioned(
                                                                  bottom: 0,
                                                                  left: 0,
                                                                  right: 0,
                                                                  child:
                                                                      Container(
                                                                    color: Colors
                                                                        .black
                                                                        .withOpacity(
                                                                            0.6),
                                                                    padding: const EdgeInsets
                                                                        .symmetric(
                                                                        vertical:
                                                                            2),
                                                                    child:
                                                                        const Text(
                                                                      "Cover",
                                                                      textAlign:
                                                                          TextAlign
                                                                              .center,
                                                                      style:
                                                                          TextStyle(
                                                                        color: Colors
                                                                            .white,
                                                                        fontSize:
                                                                            10,
                                                                        fontWeight:
                                                                            FontWeight.bold,
                                                                      ),
                                                                    ),
                                                                  ),
                                                                ),
                                                              Positioned(
                                                                top: 4,
                                                                right: 4,
                                                                child: InkWell(
                                                                  onTap: () {
                                                                    if (isCover) {
                                                                      if (addProductImageController
                                                                          .withoutColor
                                                                          .isNotEmpty) {
                                                                        addProductImageController
                                                                            .promoteToThumbnail(0);
                                                                      } else {
                                                                        addProductImageController
                                                                            .removeThumbnail();
                                                                      }
                                                                    } else {
                                                                      if (photo
                                                                          is String) {
                                                                        addProductImageController
                                                                            .deleteProductImage(
                                                                          '${widget.product?.id}',
                                                                          _getFilenameFromFullImagePath(
                                                                              photo),
                                                                          null,
                                                                        );
                                                                      } else {
                                                                        int localIdx = addProductImageController.withoutColor.indexWhere((img) =>
                                                                            img.image ==
                                                                            photo);
                                                                        if (localIdx !=
                                                                            -1) {
                                                                          addProductImageController.removeImage(
                                                                              localIdx,
                                                                              false);
                                                                        }
                                                                      }
                                                                    }
                                                                  },
                                                                  child:
                                                                      Container(
                                                                    decoration:
                                                                        const BoxDecoration(
                                                                      color: Colors
                                                                          .red,
                                                                      shape: BoxShape
                                                                          .circle,
                                                                    ),
                                                                    padding:
                                                                        const EdgeInsets
                                                                            .all(
                                                                            4),
                                                                    child:
                                                                        const Icon(
                                                                      Icons
                                                                          .close,
                                                                      color: Colors
                                                                          .white,
                                                                      size: 14,
                                                                    ),
                                                                  ),
                                                                ),
                                                              ),
                                                            ],
                                                          );
                                                        } else {
                                                          return InkWell(
                                                            onTap: () {
                                                              final hasThumbnail = addProductImageController
                                                                          .selectedLogoFile !=
                                                                      null ||
                                                                  (widget.product?.thumbnailFullUrl
                                                                              ?.path !=
                                                                          null &&
                                                                      widget
                                                                          .product!
                                                                          .thumbnailFullUrl!
                                                                          .path!
                                                                          .isNotEmpty);
                                                              if (!hasThumbnail) {
                                                                addProductImageController.pickImage(
                                                                    true,
                                                                    false,
                                                                    false,
                                                                    null,
                                                                    isAddProduct:
                                                                        widget.product ==
                                                                            null);
                                                              } else {
                                                                addProductImageController.pickImage(
                                                                    false,
                                                                    false,
                                                                    false,
                                                                    null,
                                                                    isAddProduct:
                                                                        widget.product ==
                                                                            null);
                                                              }
                                                            },
                                                            child: DottedBorder(
                                                              options:
                                                                  RoundedRectDottedBorderOptions(
                                                                dashPattern: const [
                                                                  4,
                                                                  5
                                                                ],
                                                                color: Theme.of(
                                                                        context)
                                                                    .hintColor,
                                                                radius: const Radius
                                                                    .circular(
                                                                    Dimensions
                                                                        .radiusDefault),
                                                              ),
                                                              child: Center(
                                                                child: Column(
                                                                  mainAxisAlignment:
                                                                      MainAxisAlignment
                                                                          .center,
                                                                  children: [
                                                                    Icon(
                                                                      Icons
                                                                          .add_photo_alternate_outlined,
                                                                      color: Theme.of(
                                                                              context)
                                                                          .hintColor,
                                                                      size: 28,
                                                                    ),
                                                                    const SizedBox(
                                                                        height:
                                                                            4),
                                                                    Text(
                                                                      getTranslated(
                                                                          'add_photo',
                                                                          context)!,
                                                                      style: robotoRegular
                                                                          .copyWith(
                                                                        fontSize:
                                                                            Dimensions.fontSizeSmall,
                                                                        color: Theme.of(context)
                                                                            .hintColor,
                                                                      ),
                                                                    ),
                                                                  ],
                                                                ),
                                                              ),
                                                            ),
                                                          );
                                                        }
                                                      },
                                                    );
                                                  },
                                                ),
                                                SizedBox(
                                                    height: Dimensions
                                                        .paddingSizeSmall),

                                                Center(
                                                  child: RichText(
                                                    text: TextSpan(
                                                      style:
                                                          DefaultTextStyle.of(
                                                                  context)
                                                              .style
                                                              .copyWith(
                                                                color: Theme.of(
                                                                        context)
                                                                    .hintColor,
                                                                fontSize: Dimensions
                                                                    .fontSizeDefault,
                                                              ),
                                                      children: <InlineSpan>[
                                                        TextSpan(
                                                            text: getTranslated(
                                                                    'jpg_png_less_then_1_mb',
                                                                    context) ??
                                                                ''),
                                                        TextSpan(
                                                          text: getTranslated(
                                                                  'ratio_1_1',
                                                                  context) ??
                                                              '',
                                                          style: TextStyle(
                                                              color: Theme.of(
                                                                      context)
                                                                  .hintColor),
                                                        ),
                                                      ],
                                                    ),
                                                    textAlign:
                                                        TextAlign.justify,
                                                  ),
                                                ),

                                                const SizedBox(
                                                    height: Dimensions
                                                        .paddingSizeDefault),

                                                // [AI] Deliberate Marketplace Publishing Switch
                                                Container(
                                                  padding: const EdgeInsets
                                                      .symmetric(
                                                      horizontal: Dimensions
                                                          .paddingSizeDefault,
                                                      vertical: Dimensions
                                                          .paddingSizeSmall),
                                                  decoration: BoxDecoration(
                                                    color: Theme.of(context)
                                                        .cardColor,
                                                    borderRadius:
                                                        BorderRadius.circular(
                                                            Dimensions
                                                                .radiusDefault),
                                                    border: Border.all(
                                                        color: Theme.of(context)
                                                            .primaryColor
                                                            .withValues(
                                                                alpha: 0.25)),
                                                    boxShadow: [
                                                      BoxShadow(
                                                          color:
                                                              Theme.of(context)
                                                                  .hintColor
                                                                  .withValues(
                                                                      alpha:
                                                                          0.05),
                                                          blurRadius: 4)
                                                    ],
                                                  ),
                                                  child: Row(
                                                    children: [
                                                      Icon(
                                                          Icons
                                                              .storefront_outlined,
                                                          color:
                                                              Theme.of(context)
                                                                  .primaryColor,
                                                          size: 24),
                                                      const SizedBox(
                                                          width: Dimensions
                                                              .paddingSizeSmall),
                                                      Expanded(
                                                        child: Column(
                                                          crossAxisAlignment:
                                                              CrossAxisAlignment
                                                                  .start,
                                                          children: [
                                                            Text(
                                                              getTranslated(
                                                                      'publish_to_vmarket',
                                                                      context) ??
                                                                  'Publish to Victorious MARKET',
                                                              style: robotoMedium.copyWith(
                                                                  fontSize:
                                                                      Dimensions
                                                                          .fontSizeDefault,
                                                                  color: Theme.of(
                                                                          context)
                                                                      .textTheme
                                                                      .bodyLarge
                                                                      ?.color),
                                                            ),
                                                            const SizedBox(
                                                                height: 2),
                                                            Text(
                                                              getTranslated(
                                                                      'publish_to_vmarket_desc',
                                                                      context) ??
                                                                  'Make this product visible for online marketplace orders',
                                                              style: titilliumRegular.copyWith(
                                                                  fontSize:
                                                                      Dimensions
                                                                          .fontSizeSmall,
                                                                  color: Theme.of(
                                                                          context)
                                                                      .hintColor),
                                                            ),
                                                          ],
                                                        ),
                                                      ),
                                                      Switch(
                                                        value:
                                                            _publishToMarketplace,
                                                        activeColor:
                                                            Theme.of(context)
                                                                .primaryColor,
                                                        onChanged: (val) {
                                                          setState(() {
                                                            _publishToMarketplace =
                                                                val;
                                                          });
                                                        },
                                                      ),
                                                    ],
                                                  ),
                                                ),

                                                SizedBox(
                                                    height: Dimensions
                                                        .paddingSizeDefault),
                                              ],
                                            ),
                                          ),
                                          const SizedBox(
                                              height: Dimensions
                                                  .paddingSizeDefault),
                                          const SizedBox(
                                              height: Dimensions
                                                  .paddingSizeDefault),
                                          AddProductSectionWidget(
                                            title: getTranslated(
                                                'general_setup', context)!,
                                            subTitle: getTranslated(
                                                'here_you_can_set_up_the_foundational_details',
                                                context)!,
                                            childrens: [
                                              Padding(
                                                padding:
                                                    const EdgeInsets.symmetric(
                                                        horizontal: Dimensions
                                                            .paddingSizeMedium),
                                                child: Column(
                                                  crossAxisAlignment:
                                                      CrossAxisAlignment.start,
                                                  children: [
                                                    const SizedBox(
                                                        height: Dimensions
                                                            .paddingSizeLarge),
                                                    CustomTextFieldWidget(
                                                      formProduct: true,
                                                      required: true,
                                                      border: true,
                                                      borderColor:
                                                          Theme.of(context)
                                                              .primaryColor
                                                              .withValues(
                                                                  alpha: .25),
                                                      controller: resProvider
                                                          .productCode,
                                                      textInputAction:
                                                          TextInputAction.next,
                                                      textInputType:
                                                          TextInputType.text,
                                                      isAmount: false,
                                                      hintText: getTranslated(
                                                          'product_code_sku',
                                                          context)!,
                                                    ),
                                                    const SizedBox(
                                                        height: Dimensions
                                                            .paddingSizeLarge),
                                                  ],
                                                ),
                                              ),
                                            ],
                                          ),
                                          AddProductSectionWidget(
                                            title: getTranslated(
                                                    'product_video', context) ??
                                                'Product Video',
                                            subTitle: getTranslated(
                                                    'paste_youtube_link',
                                                    context) ??
                                                'Paste a YouTube link',
                                            childrens: <Widget>[
                                              const SizedBox(
                                                  height: Dimensions
                                                      .paddingSizeSmall),
                                              Padding(
                                                padding:
                                                    const EdgeInsets.symmetric(
                                                        horizontal: Dimensions
                                                            .paddingSizeSmall),
                                                child: CustomTextFieldWidget(
                                                  border: true,
                                                  maxLine: 1,
                                                  textInputType:
                                                      TextInputType.text,
                                                  controller: resProvider
                                                      .youtubeLinkController,
                                                  textInputAction:
                                                      TextInputAction.done,
                                                  hintText: getTranslated(
                                                          'youtube_video_link',
                                                          context) ??
                                                      'YouTube video link',
                                                ),
                                              ),
                                              const SizedBox(
                                                  height: Dimensions
                                                      .paddingSizeDefault),
                                            ],
                                          ),
                                          const SizedBox(
                                              height: Dimensions
                                                  .paddingSizeDefault),
                                          Consumer<VariationController>(builder:
                                              (context, variationController,
                                                  _) {
                                            return Column(
                                              children: [
                                                Consumer<SplashController>(
                                                    builder: (context,
                                                        splashController, _) {
                                                  final configModel =
                                                      splashController
                                                          .configModel;
                                                  return AddProductSectionWidget(
                                                    title: getTranslated(
                                                        'pricing_and_others',
                                                        context)!,
                                                    subTitle: getTranslated(
                                                        'setup_product_pricing_and_stock_settings',
                                                        context)!,
                                                    childrens: [
                                                      Padding(
                                                        padding: const EdgeInsets
                                                            .symmetric(
                                                            horizontal: Dimensions
                                                                .paddingSizeMedium),
                                                        child: Column(
                                                          crossAxisAlignment:
                                                              CrossAxisAlignment
                                                                  .start,
                                                          children: [
                                                            const SizedBox(
                                                                height: Dimensions
                                                                    .paddingSizeLarge),
                                                            CustomTextFieldWidget(
                                                              border: true,
                                                              controller:
                                                                  resProvider
                                                                      .unitPriceController,
                                                              focusNode:
                                                                  _unitPriceNode,
                                                              textInputAction:
                                                                  TextInputAction
                                                                      .done,
                                                              textInputType:
                                                                  TextInputType
                                                                      .number,
                                                              isAmount: true,
                                                              hintText:
                                                                  getTranslated(
                                                                      'unit_price',
                                                                      context)!,
                                                              formProduct: true,
                                                            ),
                                                            const SizedBox(
                                                                height: Dimensions
                                                                    .paddingSizeLarge),

                                                            if (configModel
                                                                        ?.systemTaxType ==
                                                                    'product_wise' &&
                                                                configModel
                                                                        ?.systemTaxIncludeStatus ==
                                                                    0)
                                                              Consumer<
                                                                      AddProductTaxController>(
                                                                  builder: (context,
                                                                      addProductTaxController,
                                                                      child) {
                                                                return Column(
                                                                  crossAxisAlignment:
                                                                      CrossAxisAlignment
                                                                          .start,
                                                                  children: [
                                                                    DropdownDecoratorWidget(
                                                                      child: DropdownButton<
                                                                          TaxVatModel>(
                                                                        icon: const Icon(
                                                                            Icons.keyboard_arrow_down_outlined),
                                                                        borderRadius: const BorderRadius
                                                                            .all(
                                                                            Radius.circular(Dimensions.paddingEye)),
                                                                        hint: Text(
                                                                            getTranslated('select_tax_rate',
                                                                                context)!,
                                                                            style:
                                                                                robotoRegular.copyWith(color: Theme.of(context).disabledColor, fontSize: Dimensions.fontSizeExtraLarge)),
                                                                        items: addProductTaxController
                                                                            .taxVatList
                                                                            .map((TaxVatModel?
                                                                                value) {
                                                                          bool
                                                                              isSelected =
                                                                              addProductTaxController.isSelected(value!);
                                                                          return DropdownMenuItem<
                                                                              TaxVatModel>(
                                                                            enabled:
                                                                                !isSelected,
                                                                            value:
                                                                                value,
                                                                            child:
                                                                                Row(
                                                                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                                                              children: [
                                                                                Text('${value.name} (${value.taxRate}%)'),
                                                                                if (isSelected) Icon(Icons.check, color: Theme.of(context).primaryColor, size: 18),
                                                                              ],
                                                                            ),
                                                                          );
                                                                        }).toList(),
                                                                        onChanged:
                                                                            (TaxVatModel?
                                                                                value) {
                                                                          addProductTaxController
                                                                              .addToSelectedTaxList(value!);
                                                                        },
                                                                        isExpanded:
                                                                            true,
                                                                        underline:
                                                                            const SizedBox(),
                                                                      ),
                                                                    ),
                                                                    !addProductTaxController
                                                                            .selectedTaxList
                                                                            .isNotEmpty
                                                                        ? const SizedBox(
                                                                            height: Dimensions
                                                                                .paddingSizeSmall)
                                                                        : const SizedBox
                                                                            .shrink(),
                                                                    addProductTaxController
                                                                            .selectedTaxList
                                                                            .isNotEmpty
                                                                        ? SizedBox(
                                                                            height: addProductTaxController.selectedTaxList.isNotEmpty
                                                                                ? 40
                                                                                : 0,
                                                                            child:
                                                                                ListView.builder(
                                                                              itemCount: addProductTaxController.selectedTaxList.length,
                                                                              scrollDirection: Axis.horizontal,
                                                                              itemBuilder: (context, index) {
                                                                                return Padding(
                                                                                  padding: const EdgeInsets.all(Dimensions.paddingSizeVeryTiny),
                                                                                  child: Container(
                                                                                    padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeMedium),
                                                                                    margin: const EdgeInsets.only(right: Dimensions.paddingSizeExtraSmall),
                                                                                    decoration: BoxDecoration(
                                                                                      color: Theme.of(context).primaryColor.withValues(alpha: .20),
                                                                                      borderRadius: BorderRadius.circular(Dimensions.paddingSizeDefault),
                                                                                    ),
                                                                                    child: Row(children: [
                                                                                      Text(
                                                                                        '${addProductTaxController.selectedTaxList[index].name} (${addProductTaxController.selectedTaxList[index].taxRate}%)',
                                                                                        style: robotoRegular.copyWith(color: ColorHelper.blendColors(Colors.white, Theme.of(context).textTheme.bodyLarge!.color!, 0.7)),
                                                                                      ),
                                                                                      const SizedBox(width: Dimensions.paddingSizeSmall),
                                                                                      InkWell(
                                                                                        splashColor: Colors.transparent,
                                                                                        onTap: () {
                                                                                          addProductTaxController.removeToSelectedTaxList(addProductTaxController.selectedTaxList[index], index);
                                                                                        },
                                                                                        child: Icon(Icons.close, size: 15, color: ColorHelper.blendColors(Colors.white, Theme.of(context).textTheme.bodyLarge!.color!, 0.7)),
                                                                                      ),
                                                                                    ]),
                                                                                  ),
                                                                                );
                                                                              },
                                                                            ),
                                                                          )
                                                                        : const SizedBox(),
                                                                    addProductTaxController
                                                                            .selectedTaxList
                                                                            .isNotEmpty
                                                                        ? const SizedBox(
                                                                            height:
                                                                                Dimensions.paddingSizeSmall)
                                                                        : const SizedBox(),
                                                                  ],
                                                                );
                                                              }),

                                                            // [AI] Product variations & vendor discounts eliminated across Victorious MARKET.
                                                            // Pricing is a single selling price; stock availability is binary (In Stock / Out of Stock).
                                                            const SizedBox
                                                                .shrink(),
                                                          ],
                                                        ),
                                                      ),
                                                    ],
                                                  );
                                                }),
                                                const SizedBox(
                                                    height: Dimensions
                                                        .paddingSizeDefault),
                                                // [AI] Product variations eliminated across Victorious MARKET.
                                                // 6-field listing model does not use variations or multi-SKU matrices.
                                                const SizedBox(
                                                    height: Dimensions
                                                        .paddingSizeDefault),

                                                AddProductSectionWidget(
                                                  title: getTranslated(
                                                      'tags', context)!,
                                                  subTitle: getTranslated(
                                                          'add_tags_to_help_customers_find_product',
                                                          context) ??
                                                      'Tags (Optional)',
                                                  childrens: [
                                                    const SizedBox(
                                                        height: Dimensions
                                                            .paddingSizeDefault),
                                                    Padding(
                                                      padding: const EdgeInsets
                                                          .symmetric(
                                                          horizontal: Dimensions
                                                              .paddingSizeMedium),
                                                      child: TextFieldTags(
                                                        textfieldTagsController:
                                                            _controller,
                                                        initialTags:
                                                            (tagList.isNotEmpty)
                                                                ? tagList
                                                                : const [],
                                                        textSeparators: const [
                                                          ' ',
                                                          ','
                                                        ],
                                                        letterCase:
                                                            LetterCase.normal,
                                                        validator:
                                                            (String? tag) {
                                                          if (tag == 'php') {
                                                            return 'No, please just no';
                                                          } else if (_controller!
                                                              .getTags!
                                                              .contains(tag)) {
                                                            return 'you already entered that';
                                                          }
                                                          return null;
                                                        },
                                                        inputfieldBuilder:
                                                            (context,
                                                                tec,
                                                                fn,
                                                                error,
                                                                onChanged,
                                                                onSubmitted) {
                                                          return (context,
                                                              sc,
                                                              tags,
                                                              onTagDelete) {
                                                            tagList = tags;
                                                            return TextField(
                                                              controller: tec,
                                                              focusNode: fn,
                                                              decoration:
                                                                  InputDecoration(
                                                                border:
                                                                    const OutlineInputBorder(
                                                                  borderSide:
                                                                      BorderSide(
                                                                    color: Colors
                                                                        .grey,
                                                                    width: 1.0,
                                                                  ),
                                                                ),
                                                                focusedBorder:
                                                                    OutlineInputBorder(
                                                                  borderSide:
                                                                      BorderSide(
                                                                    color: Theme.of(
                                                                            context)
                                                                        .primaryColor,
                                                                    width: 1.0,
                                                                  ),
                                                                ),
                                                                helperText: '',
                                                                helperStyle:
                                                                    const TextStyle(
                                                                        color: Colors
                                                                            .grey),
                                                                hintText: _controller!
                                                                        .hasTags
                                                                    ? ''
                                                                    : "Enter tag...",
                                                                hintStyle: TextStyle(
                                                                    color: Theme.of(
                                                                            context)
                                                                        .hintColor),
                                                                errorText:
                                                                    error,
                                                                prefixIconConstraints: BoxConstraints(
                                                                    maxWidth: MediaQuery.of(context)
                                                                            .size
                                                                            .width *
                                                                        0.74),
                                                                prefixIcon: tags
                                                                        .isNotEmpty
                                                                    ? SingleChildScrollView(
                                                                        controller:
                                                                            sc,
                                                                        scrollDirection:
                                                                            Axis.horizontal,
                                                                        child: Row(
                                                                            children: tags.map((String? tag) {
                                                                          return Container(
                                                                            decoration:
                                                                                BoxDecoration(
                                                                              borderRadius: const BorderRadius.all(Radius.circular(20.0)),
                                                                              color: Theme.of(context).primaryColor,
                                                                            ),
                                                                            margin:
                                                                                const EdgeInsets.symmetric(horizontal: 5.0),
                                                                            padding:
                                                                                const EdgeInsets.symmetric(horizontal: 10.0, vertical: 5.0),
                                                                            child:
                                                                                Row(
                                                                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                                                              children: [
                                                                                InkWell(
                                                                                  child: Text('$tag', style: const TextStyle(color: Colors.white)),
                                                                                  onTap: () {
                                                                                    //print("$tag selected");
                                                                                  },
                                                                                ),
                                                                                const SizedBox(width: 4.0),
                                                                                InkWell(
                                                                                  child: const Icon(
                                                                                    Icons.cancel,
                                                                                    size: 14.0,
                                                                                    color: Colors.white,
                                                                                  ),
                                                                                  onTap: () {
                                                                                    onTagDelete(tag!);
                                                                                  },
                                                                                )
                                                                              ],
                                                                            ),
                                                                          );
                                                                        }).toList()),
                                                                      )
                                                                    : null,
                                                              ),
                                                              onChanged:
                                                                  onChanged,
                                                              onSubmitted:
                                                                  onSubmitted,
                                                            );
                                                          };
                                                        },
                                                      ),
                                                    ),
                                                    const SizedBox(
                                                        height: Dimensions
                                                            .paddingSizeDefault),
                                                  ],
                                                ),
                                                const SizedBox(
                                                    height: Dimensions
                                                        .paddingSizeDefault),
                                              ],
                                            );
                                          }),
                                          const SizedBox(
                                              height: Dimensions
                                                  .paddingSizeDefault),
                                          Container(
                                            padding: const EdgeInsets.all(
                                                Dimensions.paddingSizeDefault),
                                            decoration: BoxDecoration(
                                              color:
                                                  Theme.of(context).cardColor,
                                              boxShadow: [
                                                BoxShadow(
                                                    color: Colors.grey[
                                                        Provider.of<ThemeController>(
                                                                    context)
                                                                .darkTheme
                                                            ? 800
                                                            : 200]!,
                                                    spreadRadius: 0.5,
                                                    blurRadius: 0.3)
                                              ],
                                            ),
                                            child:
                                                Consumer<AddProductController>(
                                                    builder: (context,
                                                        resProvider, _) {
                                              return !resProvider.isLoading
                                                  ? SizedBox(
                                                      height: 50,
                                                      child: Consumer<
                                                              CategoryController>(
                                                          builder: (context,
                                                              categoryController,
                                                              _) {
                                                        return Builder(
                                                            builder: (context) {
                                                          return InkWell(
                                                            onTap: categoryController
                                                                        .categoryList ==
                                                                    null
                                                                ? null
                                                                : () async {
                                                                    AddProductImageController
                                                                        addProductImageController =
                                                                        Provider.of<AddProductImageController>(
                                                                            context,
                                                                            listen:
                                                                                false);
                                                                    CategoryController
                                                                        categoryControllerr =
                                                                        Provider.of<CategoryController>(
                                                                            context,
                                                                            listen:
                                                                                false);
                                                                    VariationController
                                                                        variationController =
                                                                        Provider.of<VariationController>(
                                                                            context,
                                                                            listen:
                                                                                false);
                                                                    SplashController
                                                                        splashController =
                                                                        Provider.of<SplashController>(
                                                                            context,
                                                                            listen:
                                                                                false);

                                                                    titleList
                                                                        .clear();
                                                                    descriptionList
                                                                        .clear();
                                                                    for (TextEditingController textEditingController
                                                                        in resProvider
                                                                            .titleControllerList) {
                                                                      titleList.add(textEditingController
                                                                          .text
                                                                          .trim());
                                                                    }
                                                                    for (TextEditingController textEditingController
                                                                        in resProvider
                                                                            .descriptionControllerList) {
                                                                      descriptionList.add(textEditingController
                                                                          .text
                                                                          .trim());
                                                                    }

                                                                    bool
                                                                        isValidateProduct =
                                                                        resProvider
                                                                            .validateGeneralInfo(
                                                                      context,
                                                                      categoryController:
                                                                          categoryControllerr,
                                                                      imageController:
                                                                          addProductImageController,
                                                                      existingProduct:
                                                                          widget
                                                                              .product,
                                                                      youtubeLink: resProvider
                                                                          .youtubeLinkController
                                                                          .text
                                                                          .trim(),
                                                                    );

                                                                    bool?
                                                                        isValidVariation =
                                                                        false;
                                                                    if (isValidateProduct) {
                                                                      final taxController = Provider.of<
                                                                              AddProductTaxController>(
                                                                          context,
                                                                          listen:
                                                                              false);
                                                                      isValidVariation =
                                                                          resProvider
                                                                              .validateVariations(
                                                                        context,
                                                                        variationController:
                                                                            variationController,
                                                                        taxController:
                                                                            taxController,
                                                                        imageController:
                                                                            addProductImageController,
                                                                        configModel:
                                                                            splashController.configModel,
                                                                        unitPrice: resProvider
                                                                            .unitPriceController
                                                                            .text
                                                                            .trim(),
                                                                        currentStock: variationController
                                                                            .totalQuantityController
                                                                            .text
                                                                            .trim(),
                                                                        orderQuantity: resProvider
                                                                            .minimumOrderQuantityController
                                                                            .text
                                                                            .trim(),
                                                                        isUpdate:
                                                                            widget.product !=
                                                                                null,
                                                                      );
                                                                    }

                                                                    if (isValidateProduct &&
                                                                        (isValidVariation ??
                                                                            false)) {
                                                                      if (Provider.of<ShopController>(context, listen: false).shopModel?.setupGuideApp !=
                                                                              null &&
                                                                          Provider.of<ShopController>(context, listen: false).shopModel?.setupGuideApp?['add_new_product'] !=
                                                                              1) {
                                                                        Provider.of<ShopController>(context,
                                                                                listen: false)
                                                                            .updateTutorialFlow('add_new_product');
                                                                        Provider.of<ShopController>(context, listen: false).updateSetupGuideApp(
                                                                            'add_new_product',
                                                                            1);
                                                                      }

                                                                      Product
                                                                          productModel =
                                                                          widget.product ??
                                                                              Product();
                                                                      AddProductModel
                                                                          addProductModel =
                                                                          widget.addProduct ??
                                                                              AddProductModel();

                                                                      addProductModel
                                                                              .titleList =
                                                                          titleList;
                                                                      addProductModel
                                                                              .descriptionList =
                                                                          descriptionList;
                                                                      addProductModel
                                                                              .productVideo =
                                                                          null;
                                                                      addProductModel.videoUrl = resProvider
                                                                          .youtubeLinkController
                                                                          .text
                                                                          .trim();

                                                                      productModel
                                                                          .taxIds = Provider.of<AddProductTaxController>(
                                                                              context,
                                                                              listen:
                                                                                  false)
                                                                          .selectedTaxList
                                                                          .map((tax) =>
                                                                              tax.id)
                                                                          .toList();
                                                                      productModel
                                                                          .taxModel = resProvider.taxTypeIndex ==
                                                                              0
                                                                          ? 'include'
                                                                          : 'exclude';
                                                                      productModel.unitPrice = PriceConverter.systemCurrencyToDefaultCurrency(
                                                                          double.parse(resProvider
                                                                              .unitPriceController
                                                                              .text
                                                                              .trim()),
                                                                          context);
                                                                      productModel
                                                                              .discount =
                                                                          0.0;
                                                                      productModel
                                                                              .productType =
                                                                          'physical';
                                                                      productModel
                                                                              .unit =
                                                                          resProvider
                                                                              .unitValue;
                                                                      productModel.code = resProvider
                                                                          .productCode
                                                                          .text
                                                                          .trim();
                                                                      productModel
                                                                              .shippingCost =
                                                                          0.0;
                                                                      productModel
                                                                          .multiplyWithQuantity = resProvider
                                                                              .isMultiply
                                                                          ? 1
                                                                          : 0;

                                                                      if (splashController.configModel!.brandSetting ==
                                                                              "1" &&
                                                                          resProvider.productTypeIndex !=
                                                                              1) {
                                                                        final productController = Provider.of<ProductController>(
                                                                            context,
                                                                            listen:
                                                                                false);
                                                                        if (productController.brandIndex !=
                                                                                null &&
                                                                            productController.brandIndex! <
                                                                                brandIds.length) {
                                                                          productModel.brandId =
                                                                              brandIds[productController.brandIndex!];
                                                                        }
                                                                      }

                                                                      // [AI] System-controlled binary stock and single price model
                                                                      productModel
                                                                              .currentStock =
                                                                          _publishToMarketplace
                                                                              ? 999
                                                                              : 0;
                                                                      productModel
                                                                          .minimumOrderQty = 1;
                                                                      productModel
                                                                              .discountType =
                                                                          'flat';
                                                                      productModel
                                                                              .status =
                                                                          _publishToMarketplace
                                                                              ? 1
                                                                              : 0;

                                                                      productModel
                                                                          .categoryIds = [];
                                                                      productModel
                                                                          .categoryIds!
                                                                          .add(CategoryIds(
                                                                              id: categoryControllerr.categoryIndex != 0 ? categoryControllerr.categoryList![categoryControllerr.categoryIndex! - 1].id.toString() : '-1'));

                                                                      if (categoryControllerr
                                                                              .subCategoryIndex !=
                                                                          0) {
                                                                        productModel
                                                                            .categoryIds!
                                                                            .add(CategoryIds(id: categoryControllerr.subCategoryList![categoryControllerr.subCategoryIndex! - 1].id.toString()));
                                                                      }

                                                                      if (categoryControllerr
                                                                              .subSubCategoryIndex !=
                                                                          0) {
                                                                        productModel
                                                                            .categoryIds!
                                                                            .add(CategoryIds(id: categoryControllerr.subSubCategoryList![categoryControllerr.subSubCategoryIndex! - 1].id.toString()));
                                                                      }

                                                                      addProductModel
                                                                          .colorCodeList = [];
                                                                      addProductModel
                                                                          .colorCodeList!
                                                                          .addAll(
                                                                              variationController.colorCodeList);

                                                                      addProductModel
                                                                          .languageList = [];
                                                                      if (splashController.configModel!.languageList !=
                                                                              null &&
                                                                          splashController
                                                                              .configModel!
                                                                              .languageList!
                                                                              .isNotEmpty) {
                                                                        for (int i =
                                                                                0;
                                                                            i < splashController.configModel!.languageList!.length;
                                                                            i++) {
                                                                          addProductModel.languageList!.insert(
                                                                              i,
                                                                              splashController.configModel!.languageList![i].code);
                                                                        }
                                                                      }

                                                                      String?
                                                                          thumbnailImage =
                                                                          widget
                                                                              .product
                                                                              ?.thumbnail;
                                                                      String?
                                                                          metaImage =
                                                                          widget
                                                                              .product
                                                                              ?.metaImage;

                                                                      final route =
                                                                          () {
                                                                        if (widget.product !=
                                                                            null) {
                                                                          resProvider.addProduct(
                                                                              context,
                                                                              productModel,
                                                                              addProductModel,
                                                                              thumbnailImage,
                                                                              metaImage,
                                                                              false,
                                                                              tagList);
                                                                        } else {
                                                                          resProvider.addProduct(
                                                                              context,
                                                                              productModel,
                                                                              addProductModel,
                                                                              thumbnailImage,
                                                                              metaImage,
                                                                              true,
                                                                              tagList);
                                                                        }
                                                                      };

                                                                      if (widget
                                                                              .product !=
                                                                          null) {
                                                                        if (addProductImageController.selectedLogoFile !=
                                                                            null) {
                                                                          await addProductImageController.addProductImage(
                                                                              context,
                                                                              addProductImageController.thumbnailImageModel!,
                                                                              route,
                                                                              update: true);
                                                                        }
                                                                        if (context
                                                                            .mounted) {
                                                                          await addProductImageController
                                                                              .onUploadColorImages(
                                                                            context:
                                                                                context,
                                                                            isUpdate:
                                                                                true,
                                                                            productId:
                                                                                productModel.id,
                                                                            callBack:
                                                                                route,
                                                                          );
                                                                        }
                                                                        if (addProductImageController
                                                                            .withoutColor
                                                                            .isNotEmpty) {
                                                                          for (int i = 0;
                                                                              i < addProductImageController.withoutColor.length;
                                                                              i++) {
                                                                            if (addProductImageController.withoutColor[i].image !=
                                                                                null) {
                                                                              await addProductImageController.addProductImage(context, addProductImageController.withoutColor[i], route, index: i, update: true);
                                                                            }
                                                                          }
                                                                        }
                                                                      } else {
                                                                        if (addProductImageController.selectedLogoFile !=
                                                                            null) {
                                                                          await addProductImageController.addProductImage(
                                                                              context,
                                                                              addProductImageController.thumbnailImageModel!,
                                                                              route);
                                                                        }
                                                                        if (addProductImageController
                                                                            .imagesWithColor
                                                                            .isNotEmpty) {
                                                                          for (int i = 0;
                                                                              i < addProductImageController.imagesWithColor.length;
                                                                              i++) {
                                                                            await addProductImageController.addProductImage(
                                                                                context,
                                                                                addProductImageController.imagesWithColor[i],
                                                                                route);
                                                                          }
                                                                        }
                                                                        if (addProductImageController
                                                                            .withoutColor
                                                                            .isNotEmpty) {
                                                                          for (int i = 0;
                                                                              i < addProductImageController.withoutColor.length;
                                                                              i++) {
                                                                            await addProductImageController.addProductImage(
                                                                                context,
                                                                                addProductImageController.withoutColor[i],
                                                                                route);
                                                                          }
                                                                        }
                                                                      }

                                                                      route();
                                                                    }
                                                                  },
                                                            child: Container(
                                                              width:
                                                                  MediaQuery.of(
                                                                          context)
                                                                      .size
                                                                      .width,
                                                              height: 40,
                                                              decoration:
                                                                  BoxDecoration(
                                                                color: categoryController
                                                                            .categoryList ==
                                                                        null
                                                                    ? Theme.of(
                                                                            context)
                                                                        .hintColor
                                                                    : Theme.of(
                                                                            context)
                                                                        .primaryColor,
                                                                borderRadius:
                                                                    BorderRadius.circular(
                                                                        Dimensions
                                                                            .paddingSizeExtraSmall),
                                                              ),
                                                              child: Center(
                                                                  child: Text(
                                                                getTranslated(
                                                                    'submit',
                                                                    context)!,
                                                                style: const TextStyle(
                                                                    color: Colors
                                                                        .white,
                                                                    fontWeight:
                                                                        FontWeight
                                                                            .w600,
                                                                    fontSize:
                                                                        Dimensions
                                                                            .fontSizeLarge),
                                                              )),
                                                            ),
                                                          );
                                                        });
                                                      }),
                                                    )
                                                  : const SizedBox();
                                            }),
                                          )
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          ],
                        );
                      })
                    : const SizedBox();
          },
        );
      }),
    );
  }

  String _generateSKU() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    final random = Random();
    String sku = '';

    for (int i = 0; i < 6; i++) {
      sku += chars[random.nextInt(chars.length)];
    }
    return sku;
  }

  List<Widget> _generateTabChildren() {
    List<Widget> tabs = [];
    for (int index = 0;
        index <
            Provider.of<SplashController>(context, listen: false)
                .configModel!
                .languageList!
                .length;
        index++) {
      tabs.add(Text(
          Provider.of<SplashController>(context, listen: false)
              .configModel!
              .languageList![index]
              .name!
              .capitalize(),
          style: robotoBold.copyWith()));
    }
    return tabs;
  }

  List<Widget> _generateTabPage(
      AddProductController resProvider, TabController? tabIndex) {
    List<Widget> tabView = [];
    for (int index = 0;
        index <
            Provider.of<SplashController>(context, listen: false)
                .configModel!
                .languageList!
                .length;
        index++) {
      tabView.add(TitleAndDescriptionWidget(
        resProvider: resProvider,
        index: index,
        langCode: Provider.of<SplashController>(context, listen: false)
                .configModel
                ?.languageList?[tabIndex?.index ?? 0]
                .code ??
            'en',
      ));
    }
    return tabView;
  }

  String _getFilenameFromFullImagePath(String url) {
    final regex = RegExp(r'([^/]+)$');
    final match = regex.firstMatch(url);
    return match?.group(1) ?? '';
  }
}

extension StringExtension on String {
  String capitalize() {
    return "${this[0].toUpperCase()}${substring(1).toLowerCase()}";
  }
}
