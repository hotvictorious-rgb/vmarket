import 'package:flutter_sixvalley_ecommerce/services/storage_service.dart';

class MemoryStorage extends StorageService {
  final values = <String, String>{};
  @override String? getString(String key) => values[key];
  @override Future<void> setString(String key, String value) async { values[key] = value; }
  @override Future<void> remove(String key) async { values.remove(key); }
}
