import 'package:flutter/material.dart';
import 'package:sixvalley_vendor_app/features/auth/controllers/auth_controller.dart';
import 'package:sixvalley_vendor_app/features/auth/domain/services/auth_service_interface.dart';
import 'package:sixvalley_vendor_app/data/model/response/base/api_response.dart';
import 'package:sixvalley_vendor_app/features/auth/widgets/reset_password_widget.dart';
import 'package:sixvalley_vendor_app/main.dart';
import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:sixvalley_vendor_app/data/datasource/remote/dio/dio_client.dart';
import 'package:sixvalley_vendor_app/features/auth/domain/models/firebase_reset_credential.dart';
import 'package:sixvalley_vendor_app/features/auth/domain/repositories/auth_repository.dart';
import 'package:sixvalley_vendor_app/services/storage_service.dart';
class ResetDio implements DioClient {
  dynamic body;
  @override Future<Response> post(String uri, {data, Map<String, dynamic>? queryParameters, Options? options, CancelToken? cancelToken, ProgressCallback? onSendProgress, ProgressCallback? onReceiveProgress}) async {
    body = data; return Response(requestOptions: RequestOptions(path: uri), statusCode: 200, data: {});
  }
  @override dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}
void main() {
  testWidgets('actual seller Firebase controller passes server identity/proof to reset widget', (tester) async {
    final observer = ResetObserver();
    await tester.pumpWidget(MaterialApp(navigatorKey: navigatorKey, navigatorObservers: [observer], home: const SizedBox()));
    final controller = AuthController(authServiceInterface: VerifiedResetService());
    await controller.firebaseOtpVerification(phoneNumber: '+234original', session: 'untrusted-session', otp: '123456', isForgetPassword: true);
    final route = observer.latest as MaterialPageRoute;
    final screen = route.builder(navigatorKey.currentContext!) as ResetPasswordWidget;
    expect(screen.mobileNumber, 'server-verified@example.test');
    expect(screen.otp, 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA');
    expect(screen.token, screen.otp);
    await tester.pumpWidget(const SizedBox()); controller.dispose();
  });
  test('native reset API forwards verified identity and OTP', () async {
    final dio = ResetDio(); final repo = AuthRepository(dioClient: dio, storageService: StorageService());
    await repo.resetPassword(' verified@example.test ', '123456', 'Password123!', 'Password123!', null);
    expect(dio.body['identity'], 'verified@example.test'); expect(dio.body['otp'], '123456');
    expect(dio.body['password'], 'Password123!'); expect(dio.body['confirm_password'], 'Password123!');
  });
  test('Firebase reset uses server-issued identity and proof instead of session or SMS code', () async {
    final credential = FirebaseResetCredential.fromResponse({'identity': 'server-verified@example.test', 'reset_token': 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'});
    expect(credential, isNotNull);
    final dio = ResetDio(); final repo = AuthRepository(dioClient: dio, storageService: StorageService());
    await repo.resetPassword(credential!.identity, credential.token, 'Password123!', 'Password123!', null);
    expect(dio.body['identity'], 'server-verified@example.test'); expect(dio.body['otp'], credential.token);
    expect(FirebaseResetCredential.fromResponse({'identity': 'attacker@example.test', 'sessionInfo': 'provider-session', 'code': '123456'}), isNull);
    expect(FirebaseResetCredential.fromResponse({'identity': '', 'reset_token': credential.token}), isNull);
  });
  test('Firebase verification API declares reset purpose explicitly', () async {
    final dio = ResetDio(); final repo = AuthRepository(dioClient: dio, storageService: StorageService());
    await repo.firebaseAuthVerify(phoneNumber: '+2348000000000', session: 'provider-session', otp: '123456', isForgetPassword: true);
    expect(dio.body['is_reset_token'], 1); expect(dio.body['sessionInfo'], 'provider-session'); expect(dio.body['code'], '123456');
  });
}


class ResetObserver extends NavigatorObserver {
  Route? latest;
  @override void didPush(Route route, Route? previousRoute) { latest = route; }
}
class VerifiedResetService implements AuthServiceInterface {
  @override Future<dynamic> firebaseAuthVerify({required String phoneNumber, required String session, required String otp, required bool isForgetPassword}) async => ApiResponse.withSuccess(Response(requestOptions: RequestOptions(path: '/firebase'), statusCode: 200, data: {'identity': 'server-verified@example.test', 'reset_token': 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'}));
  @override dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}
