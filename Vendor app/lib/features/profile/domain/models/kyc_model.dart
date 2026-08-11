class KycModel {
  String? kycStatus;
  String? nin;
  String? ninRaw;
  String? cacNumber;
  bool? hasNinDocument;
  bool? hasCacDocument;
  String? ninDocumentUrl;
  String? cacDocumentUrl;
  String? bankHolderName;
  String? bankName;
  String? accountNo;
  double? nameMatchScore;
  bool? isVerified;

  KycModel({
    this.kycStatus,
    this.nin,
    this.ninRaw,
    this.cacNumber,
    this.hasNinDocument,
    this.hasCacDocument,
    this.ninDocumentUrl,
    this.cacDocumentUrl,
    this.bankHolderName,
    this.bankName,
    this.accountNo,
    this.nameMatchScore,
    this.isVerified,
  });

  KycModel.fromJson(Map<String, dynamic> json) {
    kycStatus = json['kyc_status'];
    nin = json['nin'];
    ninRaw = json['nin_raw'];
    cacNumber = json['cac_number'];
    hasNinDocument = json['has_nin_document'] ?? false;
    hasCacDocument = json['has_cac_document'] ?? false;
    ninDocumentUrl = json['nin_document_url'];
    cacDocumentUrl = json['cac_document_url'];
    bankHolderName = json['bank_holder_name'];
    bankName = json['bank_name'];
    accountNo = json['account_no'];
    nameMatchScore = (json['name_match_score'] != null) ? double.tryParse(json['name_match_score'].toString()) : 0.0;
    isVerified = json['is_verified'] ?? false;
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['kyc_status'] = kycStatus;
    data['nin'] = nin;
    data['nin_raw'] = ninRaw;
    data['cac_number'] = cacNumber;
    data['has_nin_document'] = hasNinDocument;
    data['has_cac_document'] = hasCacDocument;
    data['nin_document_url'] = ninDocumentUrl;
    data['cac_document_url'] = cacDocumentUrl;
    data['bank_holder_name'] = bankHolderName;
    data['bank_name'] = bankName;
    data['account_no'] = accountNo;
    data['name_match_score'] = nameMatchScore;
    data['is_verified'] = isVerified;
    return data;
  }
}
