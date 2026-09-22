import 'package:sixvalley_vendor_app/data/model/response/base/api_response.dart';
import 'package:sixvalley_vendor_app/features/pickup_reservation/domain/repositories/pickup_reservation_repository_interface.dart';
import 'package:sixvalley_vendor_app/features/pickup_reservation/domain/services/pickup_reservation_service_interface.dart';

class PickupReservationService implements PickupReservationServiceInterface {
  final PickupReservationRepositoryInterface pickupReservationRepositoryInterface;

  PickupReservationService({required this.pickupReservationRepositoryInterface});

  @override
  Future<ApiResponse> verifyReservation(String code) {
    return pickupReservationRepositoryInterface.verifyReservation(code);
  }

  @override
  Future<ApiResponse> acceptReservation(String code, String? notes) {
    return pickupReservationRepositoryInterface.acceptReservation(code, notes);
  }

  @override
  Future<ApiResponse> rejectReservation(String code, String? reason) {
    return pickupReservationRepositoryInterface.rejectReservation(code, reason);
  }
}
