import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/utill/images.dart';


class CustomImageWidget extends StatelessWidget {
  final String image;
  final double? height;
  final double? width;
  final BoxFit? fit;
  final String? placeholder;
  const CustomImageWidget({super.key, required this.image, this.height, this.width, this.fit = BoxFit.cover, this.placeholder = Images.placeholder});

  @override
  Widget build(BuildContext context) {
    return CachedNetworkImage(
      placeholder: (context, url) => Image.asset(placeholder ?? Images.placeholder, height: height, width: width, fit: fit ?? BoxFit.cover),
      imageUrl: image,
      fit: fit ?? BoxFit.cover,
      height: height,
      width: width,
      memCacheHeight: height != null && height!.isFinite && height! > 0 
          ? (height! * 2.5).toInt() 
          : 600,
      memCacheWidth: width != null && width!.isFinite && width! > 0 
          ? (width! * 2.5).toInt() 
          : 600,
      maxHeightDiskCache: 1200,
      maxWidthDiskCache: 1200,
      errorWidget: (c, o, s) => Image.asset(placeholder ?? Images.placeholder, height: height, width: width, fit: fit ?? BoxFit.cover),
    );
  }
}
