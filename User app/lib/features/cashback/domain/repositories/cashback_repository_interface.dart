import 'package:flutter_sixvalley_ecommerce/interface/repo_interface.dart';

abstract class CashbackRepositoryInterface implements RepositoryInterface {
  Future<dynamic> getCashbackSummary();
  Future<dynamic> getCashbackList(int offset, int limit, {String? status});
}