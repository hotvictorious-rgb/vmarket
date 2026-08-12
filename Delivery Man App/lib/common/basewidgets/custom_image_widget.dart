import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/cupertino.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';

class CustomImageWidget extends StatelessWidget {
  final String image;
  final double? height;
  final double? width;
  final BoxFit fit;
  const CustomImageWidget({Key? key, required this.image, this.height, this.width, this.fit = BoxFit.cover}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return CachedNetworkImage(
      imageUrl: image,
      height: height,
      width: width,
      fit: fit,
      memCacheHeight: height != null && height!.isFinite && height! > 0 ? (height! * 2.5).toInt() : null,
      memCacheWidth: width != null && width!.isFinite && width! > 0 ? (width! * 2.5).toInt() : null,
      placeholder: (context, url) => Image.asset(Images.placeholder, height: height, width: width, fit: fit),
      errorWidget: (context, url, error) => Image.asset(Images.placeholder, height: height, width: width, fit: fit),
    );
  }
}
