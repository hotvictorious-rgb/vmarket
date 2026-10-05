class UserLogData {
  String? countryCode;
  String? phoneNumber;
  String? email;
  String? password;

  UserLogData({this.countryCode, this.phoneNumber,this.email, this.password});

  UserLogData.fromJson(Map<String, dynamic> json) {
    countryCode = json['country_code'];
    phoneNumber = json['phone_number'];
    password = json['password'];
    email = json['email'];
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['country_code'] = countryCode;
    data['phone_number'] = phoneNumber;
    // [AI] Credential Safety: Raw passwords must never be persisted in local storage
    data['password'] = null;
    data['email'] = email;
    return data;
  }
}
