import 'package:sixvalley_vendor_app/data/model/response/base/api_response.dart';
import 'package:sixvalley_vendor_app/interface/repository_interface.dart';

abstract class PickupReservationRepositoryInterface implements RepositoryInterface {
  Future<ApiResponse> verifyReservation(String code);
  Future<ApiResponse> acceptReservation(String code, String? notes);
  Future<ApiResponse> rejectReservation(String code, String? reason);
}
