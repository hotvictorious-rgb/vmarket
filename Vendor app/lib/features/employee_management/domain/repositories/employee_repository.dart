import 'package:dio/dio.dart';
import 'package:sixvalley_vendor_app/data/datasource/remote/dio/dio_client.dart';
import 'package:sixvalley_vendor_app/data/datasource/remote/exception/api_error_handler.dart';
import 'package:sixvalley_vendor_app/data/model/response/base/api_response.dart';
import 'package:sixvalley_vendor_app/features/employee_management/domain/repositories/employee_repository_interface.dart';
import 'package:sixvalley_vendor_app/utill/app_constants.dart';

class EmployeeRepository implements EmployeeRepositoryInterface {
  final DioClient? dioClient;
  EmployeeRepository({required this.dioClient});

  @override
  Future<ApiResponse> getEmployeeList() async {
    try {
      final response = await dioClient!.get(AppConstants.getEmployeeListUri);
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  Future<ApiResponse> addEmployee(Map<String, dynamic> body) async {
    try {
      final response = await dioClient!.post(AppConstants.addEmployeeUri, data: body);
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  Future<ApiResponse> updateEmployeeStatus(int employeeId, int status) async {
    try {
      final response = await dioClient!.post(
        AppConstants.updateEmployeeStatusUri,
        data: {'id': employeeId, 'status': status},
      );
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  Future<ApiResponse> deleteEmployee(int employeeId) async {
    try {
      final response = await dioClient!.post(
        AppConstants.deleteEmployeeUri,
        data: {'id': employeeId},
      );
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }
}
