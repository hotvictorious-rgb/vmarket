class FirebaseResetCredential {
  final String identity;
  final String token;
  const FirebaseResetCredential(this.identity, this.token);
  static FirebaseResetCredential? fromResponse(dynamic data) {
    if (data is! Map || data['identity'] is! String || data['reset_token'] is! String) return null;
    final identity = data['identity'] as String;
    final token = data['reset_token'] as String;
    if (identity.trim().isEmpty || !RegExp(r'^[A-Za-z0-9]{64}$').hasMatch(token)) return null;
    return FirebaseResetCredential(identity, token);
  }
}
