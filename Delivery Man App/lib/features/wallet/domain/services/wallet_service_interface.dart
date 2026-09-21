

abstract class WalletServiceInterface {
  Future<dynamic> getDeliveryWiseEarned({String? startDate, String? endDate, int? offset,String? type});
  }