import 'package:sixvalley_vendor_app/data/datasource/remote/dio/dio_client.dart';
import 'package:sixvalley_vendor_app/data/datasource/remote/exception/api_error_handler.dart';
import 'package:sixvalley_vendor_app/data/model/response/base/api_response.dart';
import 'package:sixvalley_vendor_app/features/pickup_reservation/domain/repositories/pickup_reservation_repository_interface.dart';
import 'package:sixvalley_vendor_app/utill/app_constants.dart';

class PickupReservationRepository implements PickupReservationRepositoryInterface {
  final DioClient dioClient;

  PickupReservationRepository({required this.dioClient});

  @override
  Future<ApiResponse> verifyReservation(String code) async {
    try {
      final response = await dioClient.post(
        AppConstants.verifyPickupReservationUri,
        data: {'reservation_code': code},
      );
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  Future<ApiResponse> acceptReservation(String code, String? notes) async {
    try {
      final response = await dioClient.post(
        AppConstants.acceptPickupReservationUri,
        data: {
          'reservation_code': code,
          'notes': notes ?? '',
        },
      );
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  Future<ApiResponse> rejectReservation(String code, String? reason) async {
    try {
      final response = await dioClient.post(
        AppConstants.rejectPickupReservationUri,
        data: {
          'reservation_code': code,
          'reason': reason ?? 'Customer declined inspection',
        },
      );
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  Future add(value) => throw UnimplementedError();

  @override
  Future delete(int id) => throw UnimplementedError();

  @override
  Future get(String id) => throw UnimplementedError();

  @override
  Future getList({int? offset}) => throw UnimplementedError();

  @override
  Future update(Map<String, dynamic> body, int id) => throw UnimplementedError();
}
