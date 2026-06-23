import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';

/// Simple API response caching service using SharedPreferences.
/// Caches GET responses to reduce network calls.
class ApiCacheService {
  static const String _prefix = 'api_cache_';
  static const Duration _defaultTtl = Duration(minutes: 5);

  final SharedPreferences _prefs;

  ApiCacheService(this._prefs);

  /// Get cached response if valid, null otherwise.
  String? get(String key) {
    final cacheKey = '$_prefix$key';
    final entry = _prefs.getString(cacheKey);
    if (entry == null) return null;

    try {
      final cache = _CacheEntry.fromJson(jsonDecode(entry));
      if (cache.isExpired) {
        _prefs.remove(cacheKey);
        return null;
      }
      return cache.data;
    } catch (_) {
      return null;
    }
  }

  /// Cache a response with default TTL.
  Future<void> set(String key, String data) => setWithTtl(key, data, _defaultTtl);

  /// Cache a response with custom TTL.
  Future<void> setWithTtl(String key, String data, Duration ttl) async {
    final cacheKey = '$_prefix$key';
    final entry = _CacheEntry(
      data: data,
      expiresAt: DateTime.now().add(ttl),
    );
    await _prefs.setString(cacheKey, jsonEncode(entry.toJson()));
  }

  /// Remove a specific cached entry.
  Future<void> remove(String key) async {
    await _prefs.remove('$_prefix$key');
  }

  /// Clear all cached responses.
  Future<void> clear() async {
    final keys = _prefs.getKeys().where((k) => k.startsWith(_prefix));
    for (final key in keys) {
      await _prefs.remove(key);
    }
  }

  /// Check if a key has a valid (non-expired) cache entry.
  bool hasValidCache(String key) => get(key) != null;
}

class _CacheEntry {
  final String data;
  final DateTime expiresAt;

  _CacheEntry({required this.data, required this.expiresAt});

  bool get isExpired => DateTime.now().isAfter(expiresAt);

  factory _CacheEntry.fromJson(Map<String, dynamic> json) {
    return _CacheEntry(
      data: json['data'] as String,
      expiresAt: DateTime.parse(json['expires_at'] as String),
    );
  }

  Map<String, dynamic> toJson() => {
        'data': data,
        'expires_at': expiresAt.toIso8601String(),
      };
}
