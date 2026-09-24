import 'dart:async';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:path/path.dart';
import 'package:http/http.dart' as http;
import 'package:dio/dio.dart';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:sixvalley_vendor_app/data/datasource/remote/dio/dio_client.dart';
import 'package:sixvalley_vendor_app/data/datasource/remote/exception/api_error_handler.dart';
import 'package:sixvalley_vendor_app/features/addProduct/domain/models/add_product_model.dart';
import 'package:sixvalley_vendor_app/data/model/response/base/api_response.dart';
import 'package:sixvalley_vendor_app/features/addProduct/domain/models/image_model.dart';
import 'package:sixvalley_vendor_app/features/product/domain/models/product_model.dart';
import 'package:sixvalley_vendor_app/features/addProduct/domain/repository/add_product_repository_interface.dart';
import 'package:sixvalley_vendor_app/main.dart';
import 'package:sixvalley_vendor_app/features/auth/controllers/auth_controller.dart';
import 'package:sixvalley_vendor_app/features/splash/controllers/splash_controller.dart';
import 'package:sixvalley_vendor_app/utill/app_constants.dart';


class AddProductRepository implements AddProductRepositoryInterface{
  final DioClient? dioClient;
  AddProductRepository({required this.dioClient});

  @override
  Future<ApiResponse> getAttributeList(String languageCode) async {
    try {
      final response = await dioClient!.get(AppConstants.attributeUri,
        options: Options(headers: {AppConstants.langKey: languageCode}),
      );
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }


  @override
  Future<ApiResponse> getEditProduct(int? id) async {
    try {
      final response = await dioClient!.get('${AppConstants.editProductUri}/$id');
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  Future<ApiResponse> getCategoryList(String languageCode) async {
    try {
      final response = await dioClient!.get(AppConstants.categoryUri,
        options: Options(headers: {AppConstants.langKey: languageCode}),
      );
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  Future<ApiResponse> getSubCategoryList() async {
    try {
      final response = await dioClient!.get(AppConstants.categoryUri);
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  Future<ApiResponse> getSubSubCategoryList() async {
    try {
      final response = await dioClient!.get(AppConstants.categoryUri);
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }

  }

  @override
  Future<ApiResponse> addImage(BuildContext context, ImageModel imageForUpload, bool colorActivate) async {
    http.MultipartRequest request = http.MultipartRequest(
        'POST', Uri.parse('${AppConstants.baseUrl}${AppConstants.uploadProductImageUri}',
    ));
    if (kDebugMode) {
      print('==image is exist or not=${imageForUpload.image!.path}');
    }
    request.headers.addAll(<String, String>{'Authorization': 'Bearer ${Provider.of<AuthController>(context,listen: false).getUserToken()}'});
    if(imageForUpload.image != null) {
      File file = File(imageForUpload.image!.path);
      final fileName = file.path.split(RegExp(r'[/\\]')).last;
      request.files.add(http.MultipartFile.fromBytes('image', file.readAsBytesSync(), filename: fileName));
    }
    Map<String, String> fields = {};
    fields.addAll(<String, String>{
      'type': imageForUpload.type!,
      'color': imageForUpload.color!,
      'colors_active' : colorActivate.toString()
    });
    request.fields.addAll(fields);
    if (kDebugMode) {
      print('=====> ${request.url.path}\n${request.fields}');
    }


    http.StreamedResponse response =
    await request.send();
    var res = await http.Response.fromStream(response);
    if (kDebugMode) {
      print('=====Response body is here==>${res.body}');
    }

    try {
      return ApiResponse.withSuccess(Response(statusCode: response.statusCode,
          requestOptions: RequestOptions(path: ''), statusMessage: response.reasonPhrase,
          data: res.body));
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }



  void _setRequestHeaders(String? token) {
    dioClient!.dio!.options.headers = {
      'Content-Type': 'application/json; charset=UTF-8',
      'Authorization': 'Bearer ${token ?? Provider.of<AuthController>(Get.context!, listen: false).getUserToken()}'
    };
  }



  Future<Map<String, dynamic>> _prepareRequestData({
    required Product product,
    required AddProductModel addProduct,
    required Map<String, dynamic> attributes,
    List<Map<String, dynamic>>? productImages,
    String? thumbnail,
    String? metaImage,
    required bool isAdd,
    required bool isActiveColor,
    required List<ColorImage> colorImageObject,
    required List<String?> tags,
  }) async {
    final fields = <String, dynamic>{};

    // Add basic product fields
    _addBasicProductFields(fields, product, addProduct, productImages, thumbnail, metaImage, isActiveColor, tags);

    // Add color images if needed
    if (addProduct.colorCodeList != null && addProduct.colorCodeList!.isEmpty) {
      fields['color_image'] = jsonEncode([]);
    } else {
      fields['color_image'] = jsonEncode(_prepareColorImages(colorImageObject));
    }

    // Add meta SEO info if available
    if (product.metaSeoInfo != null) {
      _addMetaSeoFields(fields, product.metaSeoInfo!);
    }

    // Add category hierarchy
    _addCategoryFields(fields, product.categoryIds!);

    // Handle update case
    if (!isAdd) {
      fields.addAll({'_method': 'put', 'id': product.id});
    }

    // Add attributes if present
    if (attributes.isNotEmpty) {
      fields.addAll(attributes);
    }

    return fields;
  }

  List<Map<String, dynamic>> _prepareColorImages(List<ColorImage> colorImageObject) {
    return colorImageObject
        .where((image) => image.imageName?.key != 'null' && image.imageName?.key != null)
        .map((image) => {
      'color': image.color,
      'image_name': image.imageName?.key,
      'storage': image.storage ?? 'public',
    }).toList();
  }

  void _addBasicProductFields(
      Map<String, dynamic> fields,
      Product product,
      AddProductModel addProduct,
      List<Map<String, dynamic>>? productImages,
      String? thumbnail,
      String? metaImage,
      bool isActiveColor,
      List<String?> tags
      ) {
    fields.addAll({
      'name': jsonEncode(addProduct.titleList),
      'description': jsonEncode(addProduct.descriptionList),
      'unit_price': product.unitPrice,
      'purchase_price': product.unitPrice,
      'discount': product.discount,
      'discount_type': product.discountType,
      'tax_ids': jsonEncode(product.taxIds),
      'tax_model': product.taxModel,
      'category_id': product.categoryIds![0].id,
      'unit': product.unit,
      'brand_id': Provider.of<SplashController>(Get.context!, listen: false).configModel!.brandSetting == "1"
          ? product.brandId
          : null,
      'meta_title': product.metaTitle,
      'meta_description': product.metaDescription,
      'lang': jsonEncode(addProduct.languageList),
      'colors': jsonEncode(addProduct.colorCodeList),
      'images': jsonEncode(productImages),
      'thumbnail': thumbnail,
      'colors_active': isActiveColor,
      'video_url': addProduct.videoUrl,
      'meta_image': metaImage,
      'current_stock': product.currentStock,
      'shipping_cost': product.shippingCost,
      'multiply_qty': product.multiplyWithQuantity,
      'code': product.code,
      'minimum_order_qty': product.minimumOrderQty,
      'product_type': product.productType,
      'tags': jsonEncode(tags),
    });
  }

  void _addMetaSeoFields(Map<String, dynamic> fields, MetaSeoInfo metaSeoInfo) {
    fields.addAll({
      "meta_index": metaSeoInfo.metaIndex,
      "meta_no_follow": metaSeoInfo.metaNoFollow,
      "meta_no_image_index": metaSeoInfo.metaNoImageIndex,
      "meta_no_archive": metaSeoInfo.metaNoArchive,
      "meta_no_snippet": metaSeoInfo.metaNoSnippet,
      "meta_max_snippet": metaSeoInfo.metaMaxSnippet,
      "meta_max_snippet_value": metaSeoInfo.metaMaxSnippetValue,
      "meta_max_video_preview": metaSeoInfo.metaMaxVideoPreview,
      "meta_max_video_preview_value": metaSeoInfo.metaMaxVideoPreviewValue,
      "meta_max_image_preview": metaSeoInfo.metaMaxImagePreview,
      "meta_max_image_preview_value": metaSeoInfo.metaMaxImagePreviewValue,
    });
  }

  void _addCategoryFields(Map<String, dynamic> fields, List<CategoryIds> categoryIds) {
    if (categoryIds.length > 1) {
      fields['sub_category_id'] = categoryIds[1].id;
    }
    if (categoryIds.length > 2) {
      fields['sub_sub_category_id'] = categoryIds[2].id;
    }
  }

  @override
  Future<ApiResponse> addProduct(Product product, AddProductModel addProduct, Map<String, dynamic> attributes, List<Map<String,dynamic>>? productImages, String? thumbnail, String? metaImage, bool isAdd, bool isActiveColor, List<ColorImage> colorImageObject, List<String?> tags, String? token) async {

    _setRequestHeaders(token);

    final requestData = await _prepareRequestData(
      product: product,
      addProduct: addProduct,
      attributes: attributes,
      productImages: productImages,
      thumbnail: thumbnail,
      metaImage: metaImage,
      isAdd: isAdd,
      isActiveColor: isActiveColor,
      colorImageObject: colorImageObject,
      tags: tags,
    );

    try {
      Response response;
      if (addProduct.productVideo != null) {
        List<MultipartWithKey> multiPartFiles = [];
        MultipartFile multiPartFile = MultipartFile.fromBytes(
          await addProduct.productVideo!.readAsBytes(),
          filename: basename(addProduct.productVideo!.name),
        );
        multiPartFiles.add(MultipartWithKey(key: 'product_video', multipartFile: multiPartFile));

        response = await dioClient!.postMultipart(
          '${AppConstants.baseUrl}${isAdd ? AppConstants.addProductUri : '${AppConstants.updateProductUri}/${product.id}'}',
          data: requestData,
          files: multiPartFiles,
        );
      } else {
        response = await dioClient!.post(
          '${AppConstants.baseUrl}${isAdd ? AppConstants.addProductUri : '${AppConstants.updateProductUri}/${product.id}'}',
          data: requestData,
        );
      }
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }




  @override
  Future<ApiResponse> updateProductQuantity(int? productId,int currentStock, List <Variation> variation) async {
    try {
      final response = await dioClient!.post(AppConstants.updateProductQuantity,
          data: {
            "product_id": productId,
            "current_stock": currentStock,
            "variation" : variation,
            "_method":"put"
          }
      );
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }


  @override
  Future<ApiResponse> deleteProductImage(String id, String name, String? color ) async {
    try {
      final response = await dioClient!.get("${AppConstants.deleteProductImage}?id=$id&name=$name&color=$color");
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  Future<ApiResponse> deleteProductPreview(int? id) async {
    try {
      final response = await dioClient!.get("${AppConstants.deleteProductPreview}?product_id=$id");
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }


  @override
  Future<ApiResponse> getProductImage(String id ) async {
    try {
      final response = await dioClient!.get("${AppConstants.getProductImage}$id");
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }


  @override
  Future<ApiResponse> getTaxVatList() async {
    try {
      final response = await dioClient!.get(AppConstants.getTaxVatList);
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }


  @override
  Future add(value) {
    // TODO: implement add
    throw UnimplementedError();
  }

  @override
  Future delete(int id) {
    // TODO: implement delete
    throw UnimplementedError();
  }

  @override
  Future get(String id) {
    // TODO: implement get
    throw UnimplementedError();
  }

  @override
  Future getList({int? offset = 1}) {
    // TODO: implement getList
    throw UnimplementedError();
  }

  @override
  Future update(Map<String, dynamic> body, int id) {
    // TODO: implement update
    throw UnimplementedError();
  }
}