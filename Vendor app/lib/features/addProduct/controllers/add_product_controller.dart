import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import 'package:sixvalley_vendor_app/features/addProduct/controllers/add_product_image_controller.dart';
import 'package:sixvalley_vendor_app/features/addProduct/controllers/add_product_tax_controller.dart';
import 'package:sixvalley_vendor_app/features/addProduct/controllers/digital_product_controller.dart';
import 'package:sixvalley_vendor_app/features/addProduct/controllers/variation_controller.dart';
import 'package:sixvalley_vendor_app/features/addProduct/domain/models/add_product_model.dart';
import 'package:sixvalley_vendor_app/data/model/response/base/api_response.dart';
import 'package:sixvalley_vendor_app/features/addProduct/domain/models/attribute_model.dart';
import 'package:sixvalley_vendor_app/features/addProduct/domain/models/product_image_model.dart';
import 'package:sixvalley_vendor_app/features/addProduct/domain/models/variant_type_model.dart';
import 'package:sixvalley_vendor_app/features/addProduct/domain/services/add_product_service_interface.dart';
import 'package:sixvalley_vendor_app/features/auth/controllers/auth_controller.dart';
import 'package:sixvalley_vendor_app/features/product/controllers/category_controller.dart';
import 'package:sixvalley_vendor_app/features/restock/controllers/restock_controller.dart';
import 'package:sixvalley_vendor_app/features/splash/domain/models/config_model.dart';
import 'package:sixvalley_vendor_app/features/addProduct/domain/models/edt_product_model.dart';
import 'package:sixvalley_vendor_app/features/addProduct/domain/models/image_model.dart';
import 'package:sixvalley_vendor_app/features/product/domain/models/product_model.dart';
import 'package:sixvalley_vendor_app/helper/api_checker.dart';
import 'package:sixvalley_vendor_app/helper/product_helper.dart';
import 'package:sixvalley_vendor_app/localization/language_constrants.dart';
import 'package:sixvalley_vendor_app/main.dart';
import 'package:sixvalley_vendor_app/features/product/controllers/product_controller.dart';
import 'package:sixvalley_vendor_app/features/splash/controllers/splash_controller.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_snackbar_widget.dart';
import 'package:sixvalley_vendor_app/features/dashboard/screens/dashboard_screen.dart';



class AddProductController extends ChangeNotifier {
  final AddProductServiceInterface shopServiceInterface;

  AddProductController({required this.shopServiceInterface});

  int _totalQuantity = 0;
  int get totalQuantity => _totalQuantity;
  String? _unitValue;
  String? get unitValue => _unitValue;
  int _discountTypeIndex = 0;
  int _taxTypeIndex = -1;
  String _imagePreviewSelectedType = 'large';
  int _unitIndex = 0;

  MetaSeoInfo? _metaSeoInfo = MetaSeoInfo();
  final TextEditingController maxSnippetController = TextEditingController(text: '-1');
  final TextEditingController maxImagePreviewController = TextEditingController(text: '-1');


  int get unitIndex => _unitIndex;
  bool _isLoading = false;
  bool get isLoading => _isLoading;


  bool _isAttributeActive = false;
  bool get isAttributeActive => _isAttributeActive;


  EditProductModel? _editProduct;
  EditProductModel? get editProduct => _editProduct;
  int get discountTypeIndex => _discountTypeIndex;
  int get taxTypeIndex => _taxTypeIndex;
  String get imagePreviewSelectedType => _imagePreviewSelectedType;

  bool _isMultiply = false;
  bool get isMultiply => _isMultiply;

  final picker = ImagePicker();
  List<TextEditingController> _titleControllerList = [];
  List<TextEditingController> _descriptionControllerList = [];

  List<TextEditingController>  get titleControllerList=> _titleControllerList;
  List<TextEditingController> get descriptionControllerList=> _descriptionControllerList;
  final TextEditingController _productCode = TextEditingController();
  TextEditingController get productCode => _productCode;
  List<FocusNode>? _titleNode;
  List<FocusNode>? _descriptionNode;
  List<FocusNode>? get titleNode => _titleNode;
  List<FocusNode>? get descriptionNode => _descriptionNode;
  int _productTypeIndex = 0;
  int get productTypeIndex => _productTypeIndex;


  int _totalVariantQuantity = 0;
  int get totalVariantQuantity => _totalVariantQuantity;

  List<Map<String, dynamic>>? productReturnImage  = [];
  int _variationTotalQuantity = 0;
  int get variationTotalQuantity  => _variationTotalQuantity;
  final bool _isCategoryLoading = false;
  bool get isCategoryLoading => _isCategoryLoading;
  int? _selectedPageIndex = 0;
  int? get selectedPageIndex => _selectedPageIndex;

  MetaSeoInfo? get metaSeoInfo => _metaSeoInfo;

  List<String> pages = ['general_info', 'variation_setup', 'product_seo'];
  List<String> imagePreviewType = ['large', 'medium', 'small'];


  TextEditingController unitPriceController = TextEditingController();
  TextEditingController discountController = TextEditingController();
  TextEditingController shippingCostController = TextEditingController();
  TextEditingController minimumOrderQuantityController = TextEditingController();
  TextEditingController youtubeLinkController = TextEditingController();
  XFile? _selectedVideoFile;
  bool _videoOptionUpload = true;
  XFile? get selectedVideoFile => _selectedVideoFile;
  bool get videoOptionUpload => _videoOptionUpload;

  void setVideoOption(bool isUpload) {
    _videoOptionUpload = isUpload;
    notifyListeners();
  }

  void removeVideo() {
    _selectedVideoFile = null;
    notifyListeners();
  }

  Future<void> pickVideo(BuildContext context) async {
    try {
      final XFile? file = await picker.pickVideo(
        source: ImageSource.gallery,
        maxDuration: const Duration(seconds: 60),
      );
      if (file != null) {
        final int sizeInBytes = await file.length();
        final double sizeInMb = sizeInBytes / (1024 * 1024);
        if (sizeInMb > 20) {
          showCustomSnackBarWidget(
            getTranslated('video_size_exceeds_20mb', Get.context!) ?? 'Video file size exceeds 20MB',
            Get.context!,
            sanckBarType: SnackBarType.warning,
          );
          return;
        }
        _selectedVideoFile = file;
        notifyListeners();
      }
    } catch (e) {
      debugPrint('Error picking video: $e');
    }
  }


  @override
  void dispose() {
    unitPriceController.dispose();
    discountController.dispose();
    shippingCostController.dispose();
    minimumOrderQuantityController.dispose();
    youtubeLinkController.dispose();
    super.dispose();
  }



  void setTitle(int index, String title) {
    _titleControllerList[index].text = title;
  }
  
  void setDescription(int index, String description) {
    _descriptionControllerList[index].text = description;
  }
  
  void getTitleAndDescriptionList(List<Language> languageList, EditProductModel? edtProduct){
    _titleControllerList = [];
    _descriptionControllerList = [];
    for(int i= 0; i<languageList.length; i++){
      if(edtProduct != null){
        if(i==0){
          String plainProductDetails = edtProduct.details ?? '';
          _titleControllerList.insert(i,TextEditingController(text: edtProduct.name)) ;
          _descriptionControllerList.add(TextEditingController(text: plainProductDetails)) ;
        } else{
          for (var lan in edtProduct.translations!) {
            if(lan.locale == languageList[i].code && lan.key == 'name'){
              _titleControllerList.add(TextEditingController(text: lan.value)) ;
            }
            if(lan.locale == languageList[i].code && lan.key == 'description'){
              String plainText = lan.value ?? '';
              _descriptionControllerList.add(TextEditingController(text: plainText));

              debugPrint('--------description---------${lan.value}');

            }
          }
        }
      }
      else{
        _titleControllerList.add(TextEditingController());
        _descriptionControllerList.add(TextEditingController());
      }
    }
    if(edtProduct != null){
      if(_titleControllerList.length < languageList.length) {
        int l1 = languageList.length-_titleControllerList.length;
        for(int i=0; i<l1; i++) {
          _titleControllerList.add(TextEditingController(text: editProduct!.name));
          debugPrint('--------name---------${editProduct!.name}');
        }
      }
      if(_descriptionControllerList.length < languageList.length) {
        int l0 = languageList.length-_descriptionControllerList.length;
        for(int i=0; i<l0; i++) {
          _descriptionControllerList.add(TextEditingController(text: editProduct!.details));
          debugPrint('--------description---------${editProduct!.details}');
        }
      }
    }else {
      if(_titleControllerList.length < languageList.length) {
        int l = languageList.length-_titleControllerList.length;
        for(int i=0; i<l; i++) {
          _titleControllerList.add(TextEditingController());
        }
      }
      if(_descriptionControllerList.length < languageList.length) {
        int l2 = languageList.length-_descriptionControllerList.length;
        for(int i=0; i<l2; i++) {
          _descriptionControllerList.add(TextEditingController());
        }
      }
    }
  }


  void resetDiscountTypeIndex() {
    _discountTypeIndex = 0;
  }


  String discountType= 'percent';

  void setDiscountTypeIndex(int index, bool notify) {
    _discountTypeIndex = index;
    if(_discountTypeIndex == 0){
      discountType = 'percent';
    }else{
      discountType = 'flat';
    }
    if(notify) {
      notifyListeners();
    }
  }
  
  void setTaxTypeIndex(int index, bool notify) {
    _taxTypeIndex = index;
    if(notify) {
      notifyListeners();
    }
  }

  void setImagePreviewType(String type, bool notify) {
    _imagePreviewSelectedType = type;
    if(notify) {
      notifyListeners();
    }
  }

  void toggleMultiply(BuildContext context) {
    _isMultiply = !_isMultiply;
    notifyListeners();
  }

  ///Move to Add Product Directory
  Future<void> getEditProduct(BuildContext context,int? id) async {
    _editProduct = null;
    ApiResponse response = await shopServiceInterface.getEditProduct(id);
    if (response.response != null && response.response!.statusCode == 200) {
      _editProduct = EditProductModel.fromJson(response.response!.data);
      if(_editProduct?.seoInfo != null) {
        convertSeoInfoToMetaSeoInfo(_editProduct!.seoInfo!);
      }

      getTitleAndDescriptionList(Provider.of<SplashController>(Get.context!,listen: false).configModel!.languageList!, _editProduct);
      Provider.of<DigitalProductController>(Get.context!,listen: false).initDigitalProductVariation(_editProduct!);
    } else {
      ApiChecker.checkApi(response);
    }
    notifyListeners();
  }

  void convertSeoInfoToMetaSeoInfo(SeoInfo seoInfo) {
    _metaSeoInfo = MetaSeoInfo(
      metaIndex: seoInfo.index,
      metaNoFollow: seoInfo.noFollow,
      metaNoImageIndex: seoInfo.noImageIndex,
      metaNoArchive: seoInfo.noArchive,
      metaNoSnippet: seoInfo.noSnippet,
      metaMaxSnippet: seoInfo.maxSnippet,
      metaMaxSnippetValue: seoInfo.maxSnippetValue,
      metaMaxVideoPreview: seoInfo.maxVideoPreview,
      metaMaxVideoPreviewValue: seoInfo.maxVideoPreviewValue,
      metaMaxImagePreview: seoInfo.maxImagePreview,
      metaMaxImagePreviewValue: seoInfo.maxImagePreviewValue,
      imageFullUrl: seoInfo.imageFullUrl
    );
  }



  void setUnitIndex(int index, bool notify) {
    _unitIndex = index;
    if(notify) {
      notifyListeners();
    }
  }


  int totalUploaded = 0;
  void initUpload(){
    totalUploaded = 0;
    notifyListeners();
  }





  Future<void> addProduct(BuildContext context, Product product, AddProductModel addProduct, String? thumbnail, String? metaImage, bool isAdd, List<String?> tags) async {
    _isLoading = true;
    notifyListeners();

    final addProductImageController = Provider.of<AddProductImageController>(context, listen: false);
    bool isDigitalVariationEmpty =  Provider.of<DigitalProductController>(context, listen: false).selectedDigitalVariation.isNotEmpty;

    DigitalVariationModel? digitalVariationModel;
    String? token;

    List<AttributeModel>? attributeList = Provider.of<VariationController>(context, listen: false).attributeList;

    Map<String, dynamic> variationFields = Provider.of<VariationController>(context, listen: false).processVariantData(context);

    Provider.of<VariationController>(context, listen: false).onClearColorVariations(addProduct);

    List<Map<String, dynamic>>? productReturnImages = addProductImageController.productReturnImageList;

    List<ColorImage> colorImageObjects = addProductImageController.colorImageObject;

    String? digitalProductFileName = Provider.of<DigitalProductController>(context, listen: false).digitalProductFileName;

    if(_productTypeIndex == 1) {
      digitalVariationModel = Provider.of<DigitalProductController>(context, listen: false).getDigitalVariationModel();
    } else {
      digitalVariationModel =  DigitalVariationModel();
    }

    token = Provider.of<AuthController>(context,listen: false).getUserToken();

    setMetaSeoData(product);



    ApiResponse response = await shopServiceInterface.addProduct(product, addProduct ,variationFields, productReturnImages, thumbnail, metaImage, isAdd, attributeList![0].active, colorImageObjects, tags, digitalProductFileName, digitalVariationModel, isDigitalVariationEmpty, token);
    if(response.response != null && response.response?.statusCode == 200) {

    await addProductImageController.onDeleteColorImages(product);

     _productCode.clear();
      Navigator.pushAndRemoveUntil(Get.context!, MaterialPageRoute(builder: (_) => const DashboardScreen()), (route) => false);
      showCustomSnackBarWidget(isAdd ? getTranslated('product_added_successfully', Get.context!): getTranslated('product_updated_successfully', Get.context!),Get.context!, isError: false);
       titleControllerList.clear();
      descriptionControllerList.clear();
      Provider.of<AddProductImageController>(Get.context!, listen: false).removeProductImage();
      emptyDigitalProductData();
      _isLoading = false;
      _metaSeoInfo = MetaSeoInfo();
     }else {
      Provider.of<AddProductImageController>(Get.context!,listen: false).emptyWithColorImage();
      _isLoading = false;
      ApiChecker.checkApi( response);
      showCustomSnackBarWidget(getTranslated('product_add_failed', Get.context!), Get.context!, sanckBarType: SnackBarType.error);
    }
    _isLoading = false;
    notifyListeners();
  }



  void setMetaSeoData(Product product) {
    metaSeoInfo?.metaMaxImagePreviewValue = _imagePreviewSelectedType;
    product.metaSeoInfo = metaSeoInfo;
  }

  void loadingFalse() {
    _isLoading = false;
    notifyListeners();
  }




  void setValueForUnit (String? setValue){
    if (kDebugMode) {
      debugPrint('------$setValue====$_unitValue');
    }
    _unitValue = setValue;
  }

  void setProductTypeIndex(int index, bool notify) {
    _productTypeIndex = index;
    if(notify) {
      notifyListeners();
    }
  }
  
  void setTotalVariantTotalQuantity(int total){
    _totalVariantQuantity = total;
  }

  Future<void> updateProductQuantity(BuildContext context, int? productId, int currentStock, List<Variation> variations) async {
    if(kDebugMode){
      debugPrint("variation======>${variations.length}/${variations.toList()}");
    }
    List<Variation> updatedVariations = [];
    for(int i=0; i<variations.length; i++){
      updatedVariations.add(Variation(type: variations[i].type,
          sku: variations[i].sku,
          price: variations[i].price,
          qty: int.parse( Provider.of<VariationController>(context, listen: false).variantTypeList[i].qtyController.text)
      ));
    }
    _isLoading = true;
    notifyListeners();
    ApiResponse apiResponse = await shopServiceInterface.updateProductQuantity(productId, currentStock, updatedVariations);
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      _isLoading = false;
      Navigator.pop(Get.context!);
      showCustomSnackBarWidget(getTranslated('quantity_updated_successfully', Get.context!), Get.context!, isError: false);
      Provider.of<ProductController>(Get.context!, listen: false).getStockOutProductList(1, 'en');
    } else {
      _isLoading = false;
      ApiChecker.checkApi(apiResponse);
    }
    notifyListeners();
  }


  Future<void> updateRestockProductQuantity(BuildContext context, int? productId, int currentStock, List<Variation> variations,{int? index}) async {
    if(kDebugMode){
      debugPrint("variation======>${variations.length}/${variations.toList()}");
    }
    List<Variation> updatedVariations = [];
    for(int i=0; i<variations.length; i++){
      updatedVariations.add(Variation(type: variations[i].type,
          sku: variations[i].sku,
          price: variations[i].price,
          qty: int.parse(Provider.of<VariationController>(context, listen: false).variantTypeList[i].qtyController.text)
      ));
    }
    _isLoading = true;
    notifyListeners();
    ApiResponse apiResponse = await shopServiceInterface.updateRestockProductQuantity(productId, currentStock, updatedVariations);
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      _isLoading = false;
      Navigator.pop(Get.context!);
      showCustomSnackBarWidget(getTranslated('quantity_updated_successfully', Get.context!), Get.context!, isError: false);
      await Provider.of<RestockController>(Get.context!, listen: false).getRestockProductList(1);
      // Provider.of<RestockController>(Get.context!, listen: false).removeItem(index);
    } else {
      _isLoading = false;
      ApiChecker.checkApi(apiResponse);
    }
    notifyListeners();
  }



  List<String> imagesWithoutColor = [];
  List<String> imagesWithColorForUpdate = [];
  ProductImagesModel? productImagesModel;


  void  setSelectedPageIndex (int index, {bool isUpdate = true}){
    _selectedPageIndex = index;
    if(isUpdate) {
      notifyListeners();
    }
  }


  List<String> processList(List<String> inputList) {
    return inputList.map((str) => str.toLowerCase().trim()).toList();
  }



  void updateState(){
    notifyListeners();
  }


  void emptyDigitalProductData() {
    Provider.of<DigitalProductController>(Get.context!, listen: false).emptyDigitalProductData();
  }





  


  

  void setIsAttributeActive(bool isActive, {bool notify = false}) {
    _isAttributeActive = isActive;
    if(notify) {
      notifyListeners();
    }
  }


  // Inside AddProductController class
  bool validateGeneralInfo(BuildContext context, {
    required CategoryController categoryController,
    required AddProductImageController imageController,
    required Product? existingProduct, // For update checks/ For update checks
    required String? youtubeLink,
  }) {
    bool isUpdate = existingProduct != null;

    // 1. Check Titles
    if(titleControllerList.isNotEmpty && titleControllerList[0].text.isEmpty) {
      showCustomSnackBarWidget(getTranslated('please_input_all_title', context), context, sanckBarType: SnackBarType.warning);
      return false;
    }

    // 2. Check Description
    if(descriptionControllerList.isNotEmpty && descriptionControllerList[0].text.isEmpty) {
      showCustomSnackBarWidget(getTranslated('please_input_all_des', context), context, sanckBarType: SnackBarType.warning);
      return false;
    }

    // 3. Check Category
    if (categoryController.categoryIndex == 0 || categoryController.categoryIndex == -1) {
      showCustomSnackBarWidget(getTranslated('select_a_category', context), context, sanckBarType: SnackBarType.warning);
      return false;
    }

    // 4. Check Unit (defaults to pc if not specified)
    if ((unitValue == 'select_unit' || unitValue == null) && productTypeIndex == 0) {
      unitValue = 'pc';
    }

    // 5. SKU is Optional in 6-field model (auto-generated canonical code VM-XXX-YYYY if omitted)
    if (productCode.text.isNotEmpty && productCode.text.length < 3) {
      showCustomSnackBarWidget('Product SKU must be at least 3 characters', context, sanckBarType: SnackBarType.warning);
      return false;
    }

    // 6. Check Thumbnail & Images (Image 1 serves as thumbnail if thumbnail omitted)
    bool hasExistingThumbnail = isUpdate && (existingProduct.thumbnailFullUrl?.path != null && existingProduct.thumbnailFullUrl?.path != '');
    int newImageCount = imageController.imagesWithColor.length + imageController.withoutColor.length;
    bool hasExistingImages = isUpdate && (existingProduct.imagesFullUrl != null && existingProduct.imagesFullUrl!.isNotEmpty);

    if (!isUpdate && imageController.selectedLogoFile == null && newImageCount == 0) {
      showCustomSnackBarWidget(getTranslated('upload_product_image', context), context, sanckBarType: SnackBarType.warning);
      return false;
    } else if (isUpdate && imageController.selectedLogoFile == null && !hasExistingThumbnail && newImageCount == 0 && !hasExistingImages) {
      showCustomSnackBarWidget(getTranslated('upload_product_image', context), context, sanckBarType: SnackBarType.warning);
      return false;
    }

    // 7. Check Product Images
    bool hasExistingImages = isUpdate && (existingProduct.imagesFullUrl != null && existingProduct.imagesFullUrl!.isNotEmpty);
    // Optional: Add logic to check if existing images are valid (not null path) if needed

    int newImageCount = imageController.imagesWithColor.length + imageController.withoutColor.length;

    if (!isUpdate && newImageCount == 0) {
      showCustomSnackBarWidget(getTranslated('upload_product_image', context), context, sanckBarType: SnackBarType.warning);
      return false;
    } else if (isUpdate && newImageCount == 0 && !hasExistingImages) {
      showCustomSnackBarWidget(getTranslated('upload_product_image', context), context, sanckBarType: SnackBarType.warning);
      return false;
    } else if (newImageCount > 5) {
      showCustomSnackBarWidget('Maximum 5 images allowed for product', context, sanckBarType: SnackBarType.warning);
      return false;
    } else if(youtubeLink != null && youtubeLink.trim().isNotEmpty && !youtubeLink.contains('youtube.com/embed/')) {
      showCustomSnackBarWidget(getTranslated('provide_embedded_link', context), context, sanckBarType: SnackBarType.warning);
      return false;
    }

    return true;
  }



  // Inside AddProductController class

  bool validateVariations(BuildContext context, {
    required DigitalProductController digitalProductController,
    required VariationController variationController,
    required AddProductTaxController taxController,
    required AddProductImageController imageController,
    required ConfigModel? configModel, // Pass ConfigModel from SplashController

    // Pass the text values from your UI TextControllers
    required String unitPrice,
    required String currentStock,
    required String orderQuantity,
    required bool isUpdate,
  }) {

    bool digitalProductVariationEmpty = false;
    bool isTitleEmpty = false;
    bool isFileEmpty = false;
    bool isPriceEmpty = false;
    bool isSKUEmpty = false;

    // 1. Digital Product Checks
    for (int index = 0; index < digitalProductController.selectedDigitalVariation.length; index++) {
      if(digitalProductController.digitalVariationExtantion[index].isEmpty) {
        digitalProductVariationEmpty = true;
        break;
      }
    }

    for (int index = 0; index < digitalProductController.selectedDigitalVariation.length; index++) {
      for(int i =0; i< digitalProductController.variationFileList[index].length; i++) {
        if(digitalProductController.variationFileList[index][i].fileName == null) {
          isFileEmpty = true;
          break;
        }
        if(digitalProductController.variationFileList[index][i].priceController?.text.trim() == ''){
          isPriceEmpty = true;
          break;
        }
        if(digitalProductController.variationFileList[index][i].skuController?.text.trim() == ''){
          isSKUEmpty = true;
          break;
        }
        if(digitalProductController.variationFileList[index][i].fileName == null){
          isTitleEmpty = true;
          break;
        }
      }
    }

    // 2. Physical Variant Checks
    bool haveBlankVariant = false;
    bool blankVariantPrice = false;
    bool blankVariantQuantity = false;

    if (variationController.attributeList != null) {
      for (AttributeModel attr in variationController.attributeList!) {
        if (attr.active && attr.variants.isEmpty) {
          haveBlankVariant = true;
          break;
        }
      }
    }

    for (VariantTypeModel variantType in variationController.variantTypeList) {
      if (variantType.controller.text.isEmpty) {
        blankVariantPrice = true;
        break;
      }
      if (variantType.qtyController.text.isEmpty) {
        blankVariantQuantity = true;
        break;
      }
    }

    // 3. Color Image Checks
    bool isColorImageEmpty = false;
    if(imageController.imagesWithColor.isNotEmpty) {
      for (int i=0; i<imageController.imagesWithColor.length; i++) {
        if (!isUpdate && imageController.imagesWithColor[i].image == null && !isColorImageEmpty) {
          isColorImageEmpty = true;
        } else if (isUpdate && imageController.imagesWithColor[i].colorImage?.imageName == null && !isColorImageEmpty){
          isColorImageEmpty = true;
        }
      }
    }

    // [AI] Product variations have been eliminated across Victorious MARKET.
    // Physical variant checks, variant prices, and attribute validations bypassed.
    if (unitPrice.isEmpty) {
      showCustomSnackBarWidget(getTranslated('enter_unit_price', context), context, sanckBarType: SnackBarType.warning);
      return false;
    }
    else if((isUpdate || !isUpdate) && productTypeIndex == 1 && digitalProductController.digitalProductTypeIndex == 1 && isFileEmpty) {
      showCustomSnackBarWidget(getTranslated('digital_product_file_empty', context), context, sanckBarType: SnackBarType.warning);
      return false;
    }
    else if(productTypeIndex == 1 && isPriceEmpty) {
      showCustomSnackBarWidget(getTranslated('digital_product_price_empty', context), context, sanckBarType: SnackBarType.warning);
      return false;
    }
    else if(productTypeIndex == 1 && isSKUEmpty) {
      showCustomSnackBarWidget(getTranslated('digital_product_sku_empty', context), context, sanckBarType: SnackBarType.warning);
      return false;
    }
    else if(productTypeIndex == 1 && digitalProductVariationEmpty) {
      showCustomSnackBarWidget(getTranslated('digital_product_variation_empty', context), context, sanckBarType: SnackBarType.warning);
      return false;
    }
    else if (!isUpdate && (productTypeIndex == 1 && digitalProductController.digitalProductTypeIndex == 1 &&
        digitalProductController.selectedFileForImport == null) && digitalProductController.selectedDigitalVariation.isEmpty) {
      showCustomSnackBarWidget(getTranslated('please_choose_digital_product', context), context, sanckBarType: SnackBarType.warning);
      return false;
    }
    else if(configModel?.systemTaxType == 'product_wise' && configModel?.systemTaxIncludeStatus == 0 && taxController.selectedTaxList.isEmpty) {
      showCustomSnackBarWidget(getTranslated('please_add_your_product_tax', context), context, sanckBarType: SnackBarType.warning);
      return false;
    }
    // Variations and color image requirements removed.

    return true; // Validation Passed
  }

  void resetMetaSeoInfo({bool isUpdate = false}) {
    _metaSeoInfo = MetaSeoInfo();
    if(isUpdate) {
      notifyListeners();
    }
  }



}
