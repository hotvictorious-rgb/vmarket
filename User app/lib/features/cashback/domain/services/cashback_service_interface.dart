abstract class CashbackServiceInterface {
  Future<dynamic> getCashbackSummary();
  Future<dynamic> getCashbackList(int offset, int limit, {String? status});
}