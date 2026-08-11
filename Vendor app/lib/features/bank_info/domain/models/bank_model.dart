class BankModel {
  String? name;
  String? slug;
  String? code;
  String? longcode;
  String? gateway;
  bool? active;
  int? id;

  BankModel({
    this.name,
    this.slug,
    this.code,
    this.longcode,
    this.gateway,
    this.active,
    this.id,
  });

  BankModel.fromJson(Map<String, dynamic> json) {
    name = json['name']?.toString();
    slug = json['slug']?.toString();
    code = json['code']?.toString();
    longcode = json['longcode']?.toString();
    gateway = json['gateway']?.toString();
    active = json['active'] is bool ? json['active'] : (json['active'] == 1 || json['active'] == '1');
    id = json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '');
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['name'] = name;
    data['slug'] = slug;
    data['code'] = code;
    data['longcode'] = longcode;
    data['gateway'] = gateway;
    data['active'] = active;
    data['id'] = id;
    return data;
  }
}
