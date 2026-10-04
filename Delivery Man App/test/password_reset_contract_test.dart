import 'package:flutter_test/flutter_test.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/data/api/api_client.dart';
import 'package:sixvalley_delivery_boy/features/profile/controllers/profile_controller.dart';
import 'package:sixvalley_delivery_boy/features/profile/domain/services/profile_service_interface.dart';
import 'package:sixvalley_delivery_boy/features/profile/domain/repositories/profile_repository.dart';
import 'package:sixvalley_delivery_boy/features/auth/screens/reset_password_screen.dart';

class ResetApi implements ApiClient {
  dynamic body;
  @override Future<Response> postData(String uri, dynamic body, {Map<String, String>? headers}) async {
    this.body = body; return const Response(statusCode: 200);
  }
  @override dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}
class ResetService implements ProfileServiceInterface {
  String? identity;
  String? proof;
  @override Future<dynamic> resetPassword(String? phone, String password, String confirmPassword, {required String otp}) async {
    identity = phone; proof = otp; return const Response(statusCode: 200);
  }
  @override dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}
void main() {
  test('rider repository final reset sends verified OTP with identity', () async {
    final api = ResetApi(); final repository = ProfileRepository(apiClient: api);
    await repository.resetPassword(' +2348000000000 ', 'Password123!', 'Password123!', otp: '123456');
    expect(api.body, {'identity': '+2348000000000', 'otp': '123456', 'password': 'Password123!', 'confirm_password': 'Password123!'});
  });
  test('rider reset widget and actual controller retain proof from verification', () async {
    const screen = ResetPasswordWidget(mobileNumber: '+2348000000000', otp: '123456');
    final service = ResetService(); final controller = ProfileController(profileServiceInterface: service);
    await controller.resetPassword(screen.mobileNumber, 'Password123!', 'Password123!', otp: screen.otp);
    expect(service.identity, '+2348000000000'); expect(service.proof, '123456');
    expect(controller.isLoading, false);
    controller.onClose();
  });
}
