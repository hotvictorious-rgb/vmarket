import 'dart:convert';
import 'package:http/http.dart' as http;

Future<Map<String, dynamic>> fetchPickupPaymentStatus(http.Client client,
    String baseUrl, String endpoint, String code, String token) async {
  final response = await client.get(Uri.parse('$baseUrl$endpoint/${Uri.encodeComponent(code)}/status'),
    headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'});
  if (response.statusCode != 200) throw StateError('Payment verification is unavailable');
  final data = jsonDecode(response.body);
  if (data is! Map<String, dynamic> || data['status'] != true ||
      !['unpaid', 'pending', 'paid', 'expired', 'failed', 'reconciliation_required', 'refunded'].contains(data['payment_status'])) {
    throw const FormatException('Invalid pickup payment status');
  }
  return data;
}
