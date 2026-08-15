import 'package:flutter/material.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_image_widget.dart';


class ImageDialogWidget extends StatelessWidget {
  final String imageUrl;
  const ImageDialogWidget({Key? key, required this.imageUrl}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Dialog(shape: const RoundedRectangleBorder(borderRadius: BorderRadius.all(Radius.circular(10.0))),
      child: Column(mainAxisSize: MainAxisSize.min, children: [
          Stack(children: [
              CustomImageWidget(image: imageUrl, fit: BoxFit.contain,),
              Align(alignment: Alignment.centerRight,
                child: IconButton(icon: const Icon(Icons.close),
                  onPressed: () => Navigator.of(context).pop()))])]));
  }
}
