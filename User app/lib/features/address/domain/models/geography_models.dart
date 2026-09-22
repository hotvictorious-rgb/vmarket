class CountryModel {
  int? id;
  String? name;
  String? isoCode;
  String? phoneCode;

  CountryModel({this.id, this.name, this.isoCode, this.phoneCode});

  CountryModel.fromJson(Map<String, dynamic> json) {
    id = json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '');
    name = json['name'];
    isoCode = json['iso_code'];
    phoneCode = json['phone_code']?.toString();
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['name'] = name;
    data['iso_code'] = isoCode;
    data['phone_code'] = phoneCode;
    return data;
  }
}

class StateModel {
  int? id;
  int? countryId;
  String? name;
  String? stateCode;

  StateModel({this.id, this.countryId, this.name, this.stateCode});

  StateModel.fromJson(Map<String, dynamic> json) {
    id = json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '');
    countryId = json['country_id'] is int ? json['country_id'] : int.tryParse(json['country_id']?.toString() ?? '');
    name = json['name'];
    stateCode = json['state_code'];
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['country_id'] = countryId;
    data['name'] = name;
    data['state_code'] = stateCode;
    return data;
  }
}

class LgaModel {
  int? id;
  int? stateId;
  String? name;

  LgaModel({this.id, this.stateId, this.name});

  LgaModel.fromJson(Map<String, dynamic> json) {
    id = json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '');
    stateId = json['state_id'] is int ? json['state_id'] : int.tryParse(json['state_id']?.toString() ?? '');
    name = json['name'];
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['state_id'] = stateId;
    data['name'] = name;
    return data;
  }
}
