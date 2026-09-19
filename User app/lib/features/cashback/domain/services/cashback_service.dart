import 'package:flutter_sixvalley_ecommerce/features/cashback/domain/repositories/cashback_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/cashback/domain/services/cashback_service_interface.dart';

class CashbackService implements CashbackServiceInterface {
  final CashbackRepositoryInterface cashbackRepositoryInterface;
  CashbackService({required this.cashbackRepositoryInterface});

  @override
  Future getCashbackSummary() async {
    return await cashbackRepositoryInterface.getCashbackSummary();
  }

  @override
  Future getCashbackList(int offset, int limit, {String? status}) async {
    return await cashbackRepositoryInterface.getCashbackList(offset, limit, status: status);
  }
}