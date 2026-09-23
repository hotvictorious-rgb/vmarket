import 'package:sixvalley_vendor_app/features/employee_management/domain/repositories/employee_repository_interface.dart';
import 'package:sixvalley_vendor_app/features/employee_management/domain/services/employee_service_interface.dart';

class EmployeeService implements EmployeeServiceInterface {
  final EmployeeRepositoryInterface employeeRepositoryInterface;
  EmployeeService({required this.employeeRepositoryInterface});

  @override
  Future getEmployeeList() async {
    return await employeeRepositoryInterface.getEmployeeList();
  }

  @override
  Future addEmployee(Map<String, dynamic> body) async {
    return await employeeRepositoryInterface.addEmployee(body);
  }

  @override
  Future updateEmployeeStatus(int employeeId, int status) async {
    return await employeeRepositoryInterface.updateEmployeeStatus(employeeId, status);
  }

  @override
  Future deleteEmployee(int employeeId) async {
    return await employeeRepositoryInterface.deleteEmployee(employeeId);
  }
}
