import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/controllers/auth_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/domain/services/auth_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/helper/route_healper.dart';
import 'package:flutter_sixvalley_ecommerce/main.dart';
import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_sixvalley_ecommerce/data/datasource/remote/dio/dio_client.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/domain/models/firebase_reset_credential.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/domain/repositories/auth_repository.dart';
import 'package:flutter_sixvalley_ecommerce/services/storage_service.dart';
import 'dart:async';
import 'package:provider/provider.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/screens/reset_password_screen.dart';
import 'package:flutter_sixvalley_ecommerce/theme/controllers/theme_controller.dart';

class ResetDio implements DioClient {
  dynamic body;
  @override
  Future<Response> post(String uri,
      {data,
      Map<String, dynamic>? queryParameters,
      Options? options,
      CancelToken? cancelToken,
      ProgressCallback? onSendProgress,
      ProgressCallback? onReceiveProgress}) async {
    body = data;
    return Response(
        requestOptions: RequestOptions(path: uri), statusCode: 200, data: {});
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

void main() {
  testWidgets(
      'customer reset screen preserves entered password spaces through actual controller',
      (tester) async {
    final service = EnteredPasswordService();
    final controller = AuthController(authServiceInterface: service);
    await tester.pumpWidget(MultiProvider(
        providers: [
          ChangeNotifierProvider.value(value: controller),
          ChangeNotifierProvider(
              create: (_) => ThemeController(storageService: ResetStorage())),
        ],
        child: const MaterialApp(
            home: ResetPasswordScreen(
                mobileNumber: 'verified@example.test', otp: '123456'))));
    await tester.enterText(find.byType(TextFormField).at(0), ' Password123! ');
    await tester.enterText(find.byType(TextFormField).at(1), ' Password123! ');
    tester
        .state<ResetPasswordScreenState>(find.byType(ResetPasswordScreen))
        .resetPassword();
    expect(service.password, ' Password123! ');
    expect(service.confirmPassword, service.password);
    expect(service.identity, 'verified@example.test');
    expect(service.otp, '123456');
    await tester.pumpWidget(const SizedBox());
    controller.dispose();
  });
  testWidgets(
      'actual customer Firebase controller routes only returned reset identity and credential',
      (tester) async {
    Uri? captured;
    final router = GoRouter(navigatorKey: navigatorKey, routes: [
      GoRoute(path: '/', builder: (_, __) => const SizedBox()),
      GoRoute(
          path: RouterHelper.resetPasswordScreen,
          builder: (_, state) {
            captured = state.uri;
            return const SizedBox();
          })
    ]);
    await tester.pumpWidget(MaterialApp.router(routerConfig: router));
    final controller =
        AuthController(authServiceInterface: VerifiedResetService());
    await controller.firebaseOtpLogin(
        phoneNumber: '+234original',
        session: 'untrusted-session',
        otp: '123456',
        isForgetPassword: true);
    await tester.pumpAndSettle();
    expect(captured?.queryParameters['mobileNumber'],
        'server-verified@example.test');
    expect(captured?.queryParameters['otp'],
        'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA');
    await tester.pumpWidget(const SizedBox());
    router.dispose();
    controller.dispose();
  });
  test('native reset API forwards verified identity and OTP', () async {
    final dio = ResetDio();
    final repo =
        AuthRepository(dioClient: dio, storageService: StorageService());
    await repo.resetPassword(
        ' verified@example.test ', '123456', 'Password123!', 'Password123!');
    expect(dio.body['identity'], 'verified@example.test');
    expect(dio.body['otp'], '123456');
    expect(dio.body['password'], 'Password123!');
    expect(dio.body['confirm_password'], 'Password123!');
  });
  test(
      'Firebase reset uses server-issued identity and proof instead of session or SMS code',
      () async {
    final credential = FirebaseResetCredential.fromResponse({
      'identity': 'server-verified@example.test',
      'reset_token':
          'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'
    });
    expect(credential, isNotNull);
    final dio = ResetDio();
    final repo =
        AuthRepository(dioClient: dio, storageService: StorageService());
    await repo.resetPassword(
        credential!.identity, credential.token, 'Password123!', 'Password123!');
    expect(dio.body['identity'], 'server-verified@example.test');
    expect(dio.body['otp'], credential.token);
    expect(
        FirebaseResetCredential.fromResponse({
          'identity': 'attacker@example.test',
          'sessionInfo': 'provider-session',
          'code': '123456'
        }),
        isNull);
    expect(
        FirebaseResetCredential.fromResponse(
            {'identity': '', 'reset_token': credential.token}),
        isNull);
  });
  test('Firebase verification API declares reset purpose explicitly', () async {
    final dio = ResetDio();
    final repo =
        AuthRepository(dioClient: dio, storageService: StorageService());
    await repo.firebaseAuthVerify(
        phoneNumber: '+2348000000000',
        session: 'provider-session',
        otp: '123456',
        isForgetPassword: true);
    expect(dio.body['is_reset_token'], 1);
    expect(dio.body['sessionInfo'], 'provider-session');
    expect(dio.body['code'], '123456');
  });
}

class VerifiedResetService implements AuthServiceInterface {
  @override
  Future<dynamic> firebaseAuthVerify(
          {required String phoneNumber,
          required String session,
          required String otp,
          required bool isForgetPassword}) async =>
      ApiResponseModel.withSuccess(Response(
          requestOptions: RequestOptions(path: '/firebase'),
          statusCode: 200,
          data: {
            'identity': 'server-verified@example.test',
            'reset_token':
                'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'
          }));
  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

class ResetStorage implements StorageService {
  @override
  bool? getBool(String key) => false;
  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

class EnteredPasswordService extends VerifiedResetService {
  String? password, confirmPassword, identity, otp;
  @override
  Future<dynamic> resetPassword(
      String identity, String otp, String password, String confirmPassword) {
    this.identity = identity;
    this.otp = otp;
    this.password = password;
    this.confirmPassword = confirmPassword;
    return Completer<dynamic>().future;
  }
}
