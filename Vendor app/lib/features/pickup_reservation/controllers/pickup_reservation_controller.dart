import 'package:flutter/material.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_snackbar_widget.dart';
import 'package:sixvalley_vendor_app/data/model/response/base/api_response.dart';
import 'package:sixvalley_vendor_app/features/pickup_reservation/domain/services/pickup_reservation_service_interface.dart';
import 'package:sixvalley_vendor_app/helper/api_checker.dart';

class PickupReservationController with ChangeNotifier {
  final PickupReservationServiceInterface reservationServiceInterface;

  PickupReservationController({required this.reservationServiceInterface});

  bool _isLoading = false;
  bool get isLoading => _isLoading;

  Map<String, dynamic>? _verifiedData;
  Map<String, dynamic>? get verifiedData => _verifiedData;

  void reset() {
    _verifiedData = null;
    _isLoading = false;
    notifyListeners();
  }

  Future<bool> verifyReservationCode(String code, BuildContext context) async {
    _isLoading = true;
    _verifiedData = null;
    notifyListeners();

    ApiResponse apiResponse = await reservationServiceInterface.verifyReservation(code.trim());
    _isLoading = false;
    notifyListeners();

    if (!context.mounted) return false;

    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      final resData = apiResponse.response!.data;
      if (resData['status'] == true) {
        _verifiedData = resData['data'];
        showCustomSnackBarWidget(
          resData['message'] ?? 'Reservation code verified successfully',
          context,
          isToaster: true,
          isError: false,
          sanckBarType: SnackBarType.success,
        );
        notifyListeners();
        return true;
      } else {
        showCustomSnackBarWidget(
          resData['message'] ?? 'Verification failed',
          context,
          isToaster: true,
          isError: true,
          sanckBarType: SnackBarType.error,
        );
        return false;
      }
    } else {
      ApiChecker.checkApi(apiResponse);
      return false;
    }
  }

  Future<bool> acceptInspection(String code, String? notes, BuildContext context) async {
    _isLoading = true;
    notifyListeners();

    ApiResponse apiResponse = await reservationServiceInterface.acceptReservation(code.trim(), notes);
    _isLoading = false;
    notifyListeners();

    if (!context.mounted) return false;

    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      final resData = apiResponse.response!.data;
      showCustomSnackBarWidget(
        resData['message'] ?? 'Inspection accepted. Customer can proceed with payment.',
        context,
        isToaster: true,
        isError: false,
        sanckBarType: SnackBarType.success,
      );
      reset();
      return true;
    } else {
      ApiChecker.checkApi(apiResponse);
      return false;
    }
  }

  Future<bool> rejectInspection(String code, String? reason, BuildContext context) async {
    _isLoading = true;
    notifyListeners();

    ApiResponse apiResponse = await reservationServiceInterface.rejectReservation(code.trim(), reason);
    _isLoading = false;
    notifyListeners();

    if (!context.mounted) return false;

    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      final resData = apiResponse.response!.data;
      showCustomSnackBarWidget(
        resData['message'] ?? 'Inspection rejected and items released back to stock.',
        context,
        isToaster: true,
        isError: false,
        sanckBarType: SnackBarType.success,
      );
      reset();
      return true;
    } else {
      ApiChecker.checkApi(apiResponse);
      return false;
    }
  }
}
