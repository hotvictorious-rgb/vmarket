import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:url_launcher/url_launcher.dart';

/// [AI] NavigationHelper provides zero-cost, zero-API-key turn-by-turn
/// GPS navigation by deep-linking directly into the rider's native Google Maps app.
class NavigationHelper {
  static Future<void> openGoogleMaps({
    double? latitude,
    double? longitude,
    String? address,
    String? customerName,
  }) async {
    final bool hasValidCoords = latitude != null &&
        longitude != null &&
        latitude != 0.0 &&
        longitude != 0.0 &&
        latitude.isFinite &&
        longitude.isFinite;

    final bool hasAddress = address != null && address.trim().isNotEmpty;

    if (!hasValidCoords && !hasAddress) {
      Get.snackbar(
        'Location Unavailable',
        'Customer address or GPS coordinates are not available for this order.',
        snackPosition: SnackPosition.BOTTOM,
        backgroundColor: Colors.redAccent,
        colorText: Colors.white,
        margin: const EdgeInsets.all(16),
        duration: const Duration(seconds: 3),
      );
      return;
    }

    Uri? primaryUri;
    Uri? fallbackUri;

    if (hasValidCoords) {
      // 1. Direct turn-by-turn driving navigation intent on Android
      primaryUri = Uri.parse('google.navigation:q=$latitude,$longitude&mode=d');
      // 2. Standard Google Maps universal routing intent (Android/iOS)
      fallbackUri = Uri.parse(
        'https://www.google.com/maps/dir/?api=1&destination=$latitude,$longitude&travelmode=driving',
      );
    } else {
      // Address string fallback (e.g., "14 Aka Road, Uyo, Akwa Ibom")
      final encodedAddress = Uri.encodeComponent(address!.trim());
      primaryUri = Uri.parse('geo:0,0?q=$encodedAddress');
      fallbackUri = Uri.parse(
        'https://www.google.com/maps/search/?api=1&query=$encodedAddress',
      );
    }

    try {
      if (await canLaunchUrl(primaryUri)) {
        await launchUrl(primaryUri, mode: LaunchMode.externalApplication);
      } else if (await canLaunchUrl(fallbackUri)) {
        await launchUrl(fallbackUri, mode: LaunchMode.externalApplication);
      } else {
        await launchUrl(fallbackUri, mode: LaunchMode.platformDefault);
      }
    } catch (e) {
      if (await canLaunchUrl(fallbackUri)) {
        await launchUrl(fallbackUri, mode: LaunchMode.externalApplication);
      } else {
        Get.snackbar(
          'Navigation Error',
          'Could not open Google Maps. Please ensure Google Maps is installed on your phone.',
          snackPosition: SnackPosition.BOTTOM,
          backgroundColor: Colors.redAccent,
          colorText: Colors.white,
          margin: const EdgeInsets.all(16),
          duration: const Duration(seconds: 4),
        );
      }
    }
  }
}
