class EmployeeModel {
  int? id;
  String? name;
  String? email;
  String? phone;
  int? roleId;
  String? roleName;
  int? shopId;
  bool? status;
  String? createdAt;

  EmployeeModel({
    this.id,
    this.name,
    this.email,
    this.phone,
    this.roleId,
    this.roleName,
    this.shopId,
    this.status,
    this.createdAt,
  });

  EmployeeModel.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    name = json['name'] ?? json['f_name'];
    email = json['email'];
    phone = json['phone'];
    roleId = json['role_id'];
    roleName = json['role_name'] ?? json['role']?['name'];
    shopId = json['shop_id'];
    status = json['status'] == 1 || json['status'] == true;
    createdAt = json['created_at'];
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['name'] = name;
    data['email'] = email;
    data['phone'] = phone;
    data['role_id'] = roleId;
    data['shop_id'] = shopId;
    data['status'] = (status ?? false) ? 1 : 0;
    return data;
  }
}
