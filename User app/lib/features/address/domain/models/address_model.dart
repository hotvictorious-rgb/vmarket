import 'package:flutter_sixvalley_ecommerce/features/address/domain/models/geography_models.dart';

class AddressModel {
  int? id;
  String? contactPersonName;
  String? addressType;
  String? address;
  String? city;
  String? zip;
  String? phone;
  String? createdAt;
  String? updatedAt;
  String? state;
  String? country;
  String? latitude;
  String? longitude;
  bool? isBilling;
  String? guestId;
  String? email;

  // [AI] Canonical Geography Fields (Country -> State -> LGA)
  int? countryId;
  int? stateId;
  int? lgaId;
  String? lgaName;
  CountryModel? countryData;
  StateModel? stateData;
  LgaModel? lgaData;

  AddressModel({
    this.id,
    this.contactPersonName,
    this.addressType,
    this.address,
    this.city,
    this.zip,
    this.phone,
    this.createdAt,
    this.updatedAt,
    this.state,
    this.country,
    this.latitude,
    this.longitude,
    this.isBilling,
    this.guestId,
    this.email,
    this.countryId,
    this.stateId,
    this.lgaId,
    this.lgaName,
    this.countryData,
    this.stateData,
    this.lgaData,
  });

  AddressModel.fromJson(Map<String, dynamic> json) {
    id = json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '');
    contactPersonName = json['contact_person_name'];
    addressType = json['address_type'];
    address = json['address'];
    city = json['city'];
    zip = json['zip'];
    phone = json['phone'];
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
    latitude = json['latitude'];
    longitude = json['longitude'];
    isBilling = json['is_billing'] ?? false;
    email = json['email'];
    guestId = json['guest_id']?.toString();

    // Canonical Geography IDs
    countryId = json['country_id'] is int ? json['country_id'] : int.tryParse(json['country_id']?.toString() ?? '');
    stateId = json['state_id'] is int ? json['state_id'] : int.tryParse(json['state_id']?.toString() ?? '');
    lgaId = json['lga_id'] is int ? json['lga_id'] : int.tryParse(json['lga_id']?.toString() ?? '');

    // Canonical Country relation or string
    if (json['country'] is Map<String, dynamic>) {
      countryData = CountryModel.fromJson(json['country']);
      country = countryData?.name;
      countryId ??= countryData?.id;
    } else {
      country = json['country']?.toString();
    }

    // Canonical State relation or string
    if (json['state'] is Map<String, dynamic>) {
      stateData = StateModel.fromJson(json['state']);
      state = stateData?.name;
      stateId ??= stateData?.id;
    } else {
      state = json['state']?.toString();
    }

    // Canonical LGA relation or string
    if (json['lga'] is Map<String, dynamic>) {
      lgaData = LgaModel.fromJson(json['lga']);
      lgaName = lgaData?.name;
      lgaId ??= lgaData?.id;
      city ??= lgaName;
    } else if (json['lga'] != null) {
      lgaName = json['lga']?.toString();
    } else if (city != null && city!.isNotEmpty) {
      lgaName = city;
    }
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['contact_person_name'] = contactPersonName;
    data['address_type'] = addressType;
    data['address'] = address;
    data['city'] = city;
    data['zip'] = zip;
    data['phone'] = phone;
    data['created_at'] = createdAt;
    data['updated_at'] = updatedAt;
    data['state'] = state;
    data['country'] = country;
    data['latitude'] = latitude;
    data['longitude'] = longitude;
    data['is_billing'] = isBilling;
    data['guest_id'] = guestId;
    data['email'] = email;

    // Canonical Geography
    if (countryId != null) data['country_id'] = countryId;
    if (stateId != null) data['state_id'] = stateId;
    if (lgaId != null) data['lga_id'] = lgaId;
    return data;
  }
}
