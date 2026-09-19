import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/features/cashback/domain/models/cashback_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/cashback/domain/services/cashback_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/helper/api_checker.dart';

class CashbackController with ChangeNotifier {
  final CashbackServiceInterface cashbackServiceInterface;
  CashbackController({required this.cashbackServiceInterface});

  bool _isLoading = false;
  bool get isLoading => _isLoading;

  CashbackSummaryModel? _cashbackSummary;
  CashbackSummaryModel? get cashbackSummary => _cashbackSummary;

  List<CashbackLedgerItem> _cashbackList = [];
  List<CashbackLedgerItem> get cashbackList => _cashbackList;

  int _totalSize = 0;
  int get totalSize => _totalSize;

  Future<void> getCashbackSummary({bool reload = false}) async {
    if (_cashbackSummary == null || reload) {
      _isLoading = true;
      if (reload) notifyListeners();
    }
    ApiResponseModel apiResponse = await cashbackServiceInterface.getCashbackSummary();
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      _cashbackSummary = CashbackSummaryModel.fromJson(apiResponse.response!.data);
    } else {
      ApiChecker.checkApi(apiResponse);
    }
    _isLoading = false;
    notifyListeners();
  }

  Future<void> getCashbackList(int offset, {bool reload = false, String? status}) async {
    if (reload) {
      _cashbackList = [];
      _isLoading = true;
      notifyListeners();
    }
    ApiResponseModel apiResponse = await cashbackServiceInterface.getCashbackList(offset, 10, status: status);
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      if (reload) {
        _cashbackList = [];
      }
      _totalSize = apiResponse.response!.data['total_size'] ?? 0;
      if (apiResponse.response!.data['ledgers'] != null) {
        apiResponse.response!.data['ledgers'].forEach((item) {
          _cashbackList.add(CashbackLedgerItem.fromJson(item));
        });
      }
    } else {
      ApiChecker.checkApi(apiResponse);
    }
    _isLoading = false;
    notifyListeners();
  }
}