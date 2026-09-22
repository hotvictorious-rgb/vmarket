import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// [AI] Unified StorageService for User App
/// Wraps FlutterSecureStorage with a synchronized in-memory cache.
/// Guarantees zero boot races, single source of truth, and instant synchronous reads.
class StorageService {
  final FlutterSecureStorage _secureStorage;
  final Map<String, dynamic> _cache = {};

  StorageService({FlutterSecureStorage? secureStorage})
      : _secureStorage = secureStorage ?? const FlutterSecureStorage();

  /// Asynchronously loads all persisted keys into memory at startup.
  Future<void> init() async {
    try {
      final allEntries = await _secureStorage.readAll();
      _cache.addAll(allEntries);
    } catch (e) {
      debugPrint('StorageService init error: $e');
    }
  }

  // --- Synchronous Getters ---
  String? getString(String key) {
    final val = _cache[key];
    return val is String ? val : val?.toString();
  }

  bool? getBool(String key) {
    final val = _cache[key];
    if (val == null) return null;
    if (val is bool) return val;
    if (val == 'true' || val == '1') return true;
    if (val == 'false' || val == '0') return false;
    return null;
  }

  int? getInt(String key) {
    final val = _cache[key];
    if (val == null) return null;
    if (val is int) return val;
    return int.tryParse(val.toString());
  }

  double? getDouble(String key) {
    final val = _cache[key];
    if (val == null) return null;
    if (val is double) return val;
    return double.tryParse(val.toString());
  }

  List<String>? getStringList(String key) {
    final val = _cache[key];
    if (val == null) return null;
    if (val is List<String>) return val;
    if (val is List) return val.map((e) => e.toString()).toList();
    if (val is String) {
      try {
        final decoded = jsonDecode(val);
        if (decoded is List) {
          return decoded.map((e) => e.toString()).toList();
        }
      } catch (_) {}
    }
    return null;
  }

  bool containsKey(String key) => _cache.containsKey(key);

  Set<String> getKeys() => _cache.keys.toSet();

  // --- Asynchronous Setters & Deleters ---
  Future<void> setString(String key, String value) async {
    _cache[key] = value;
    await _secureStorage.write(key: key, value: value);
  }

  Future<void> setBool(String key, bool value) async {
    _cache[key] = value;
    await _secureStorage.write(key: key, value: value.toString());
  }

  Future<void> setInt(String key, int value) async {
    _cache[key] = value;
    await _secureStorage.write(key: key, value: value.toString());
  }

  Future<void> setDouble(String key, double value) async {
    _cache[key] = value;
    await _secureStorage.write(key: key, value: value.toString());
  }

  Future<void> setStringList(String key, List<String> value) async {
    _cache[key] = value;
    await _secureStorage.write(key: key, value: jsonEncode(value));
  }

  Future<void> remove(String key) async {
    _cache.remove(key);
    await _secureStorage.delete(key: key);
  }

  Future<void> clear() async {
    _cache.clear();
    await _secureStorage.deleteAll();
  }
}
