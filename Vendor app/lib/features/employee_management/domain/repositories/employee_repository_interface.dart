import 'package:sixvalley_vendor_app/data/model/response/base/api_response.dart';

abstract class EmployeeRepositoryInterface {
  Future<ApiResponse> getEmployeeList();
  Future<ApiResponse> addEmployee(Map<String, dynamic> body);
  Future<ApiResponse> updateEmployeeStatus(int employeeId, int status);
  Future<ApiResponse> deleteEmployee(int employeeId);
}
