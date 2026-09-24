import 'package:flutter/cupertino.dart';
import 'package:sixvalley_vendor_app/features/addProduct/domain/models/add_product_model.dart';
import 'package:sixvalley_vendor_app/features/product/domain/models/product_model.dart';
import 'package:sixvalley_vendor_app/features/addProduct/domain/models/image_model.dart';

abstract class AddProductServiceInterface {
  Future<dynamic> getAttributeList(String languageCode);
  Future<dynamic> getEditProduct(int? id);
  Future<dynamic> getCategoryList(String languageCode);
  Future<dynamic> getSubCategoryList();
  Future<dynamic> getSubSubCategoryList();
  Future<dynamic> addImage(BuildContext context, ImageModel imageForUpload, bool colorActivate);
  Future<dynamic> addProduct(Product product, AddProductModel addProduct, Map<String, dynamic> attributes, List<Map<String, dynamic>>? productImages, String? thumbnail, String? metaImage, bool isAdd, bool isActiveColor, List<ColorImage> colorImageObject, List<String?> tags, String? token);
  Future<dynamic> updateProductQuantity(int? productId,int currentStock, List <Variation> variation);
  Future<dynamic> deleteProductImage(String id, String name, String? color );
  Future<dynamic> getProductImage(String id );
  Future<dynamic> deleteProductPreview(int? id);
  Future<dynamic> getTaxVatList();
}