# Seller Mobile App - Product Video Upload Integration Guide

To allow vendors to upload video files directly from the Seller Mobile App (Flutter), your app developer just needs to send the video file directly inside the standard product create/edit API request payloads. 

Because we implemented a global Eloquent event hook on the backend, the server will automatically handle uploading the file to YouTube and cleaning it up. No additional API requests or complex OAuth integrations are required on the mobile app side!

---

## 1. API Endpoints

* **Add Product:** `POST` to `/api/v2/seller/products/add` or `/api/v3/seller/products/add`
* **Update Product:** `POST` to `/api/v2/seller/products/update/{id}` or `/api/v3/seller/products/update/{id}`

---

## 2. Multipart Form Parameters

Send the video file in the multipart request alongside your other product parameters:

| Field Name | Type | Limit | Description |
| :--- | :--- | :--- | :--- |
| `product_video` | File (Binary) | **Max 20MB** | The video file selected from the phone's gallery (mp4, webm, mov, avi, mkv). |
| `video_url` | String | Optional | (Fallback) Paste YouTube link if the vendor chooses to enter a manual YouTube link instead. |

---

## 3. Flutter (Dart) Implementation Example

Here is a standard example using the popular `dio` package in Dart to send the video file:

```dart
import 'dart:io';
import 'package:dio/dio.dart' as dio;

Future<void> saveProduct({
  required String productName,
  required double price,
  required File? videoFile,
  String? manualVideoLink,
}) async {
  final url = "https://shop.victoriousmarket.com.ng/api/v3/seller/products/add";
  final dioClient = dio.Dio();

  // Create multipart payload
  final formDataMap = <String, dynamic>{
    'name': productName,
    'unit_price': price,
    // ... other product fields (category_id, code, etc.)
  };

  // If a local video file was selected, attach it
  if (videoFile != null) {
    formDataMap['product_video'] = await dio.MultipartFile.fromFile(
      videoFile.path,
      filename: videoFile.path.split('/').last,
    );
  } else if (manualVideoLink != null && manualVideoLink.isNotEmpty) {
    // Fallback: If they pasted a link instead
    formDataMap['video_url'] = manualVideoLink;
  }

  final formData = dio.FormData.fromMap(formDataMap);

  try {
    final response = await dioClient.post(
      url,
      data: formData,
      options: dio.Options(
        headers: {
          'Authorization': 'Bearer YOUR_SELLER_TOKEN',
          'Accept': 'application/json',
        },
      ),
    );

    if (response.statusCode == 200) {
      print("Product saved successfully with video upload!");
    } else {
      print("Error saving product: ${response.data}");
    }
  } catch (e) {
    print("Upload error: $e");
  }
}
```

---

## 4. Key Developer Notes

1. **Size Validation:** You must validate that the file is **<= 20MB** in the Flutter code before initiating the upload, to give the seller instant feedback.
2. **Duration Validation:** We recommend warning the user if the video length exceeds **60 seconds (1 minute)**.
3. **Mime Types:** Restrict the media picker to standard formats (`mp4`, `mov`, `webm`, `avi`, `mkv`).
