import 'package:flutter/material.dart';
import 'package:sixvalley_vendor_app/data/model/response/base/api_response.dart';
import 'package:sixvalley_vendor_app/features/employee_management/domain/models/employee_model.dart';
import 'package:sixvalley_vendor_app/features/employee_management/domain/services/employee_service_interface.dart';
import 'package:sixvalley_vendor_app/helper/api_checker.dart';

class EmployeeController with ChangeNotifier {
  final EmployeeServiceInterface employeeServiceInterface;
  EmployeeController({required this.employeeServiceInterface});

  List<EmployeeModel>? _employeeList;
  List<EmployeeModel>? get employeeList => _employeeList;

  bool _isLoading = false;
  bool get isLoading => _isLoading;

  Future<void> getEmployeeList() async {
    _isLoading = true;
    notifyListeners();

    ApiResponse apiResponse = await employeeServiceInterface.getEmployeeList();
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      _employeeList = [];
      apiResponse.response!.data.forEach((emp) {
        _employeeList!.add(EmployeeModel.fromJson(emp));
      });
    } else {
      ApiChecker.checkApi(apiResponse);
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<bool> updateStatus(int employeeId, bool currentStatus) async {
    _isLoading = true;
    notifyListeners();

    int newStatus = currentStatus ? 0 : 1;
    ApiResponse apiResponse = await employeeServiceInterface.updateEmployeeStatus(employeeId, newStatus);
    bool isSuccess = false;

    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      isSuccess = true;
      getEmployeeList();
    } else {
      ApiChecker.checkApi(apiResponse);
    }

    _isLoading = false;
    notifyListeners();
    return isSuccess;
  }
}
