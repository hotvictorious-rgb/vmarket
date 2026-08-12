import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/cupertino.dart';
import 'package:sixvalley_vendor_app/utill/images.dart';

class CustomImageWidget extends StatelessWidget {
  final String image;
  final double? height;
  final double? width;
  final BoxFit fit;
  final String? placeholder;
  const CustomImageWidget({super.key, required this.image, this.height, this.width, this.fit = BoxFit.cover, this.placeholder = Images.placeholderImage});

  @override
  Widget build(BuildContext context) {
    return CachedNetworkImage(
      placeholder: (context, url) => Image.asset(placeholder ?? Images.placeholderImage, height: height, width: width, fit: fit),
      imageUrl: image,
      fit: fit,
      height: height,
      width: width,
      memCacheHeight: height != null && height!.isFinite && height! > 0 ? (height! * 2.5).toInt() : null,
      memCacheWidth: width != null && width!.isFinite && width! > 0 ? (width! * 2.5).toInt() : null,
      errorWidget: (c, o, s) => Image.asset(placeholder ?? Images.placeholderImage, height: height, width: width, fit: fit),
    );
  }
}
