import 'dart:async';
import 'dart:convert';
import 'dart:math';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:get/get.dart';
import 'package:path/path.dart' as p;
import 'package:sqflite/sqflite.dart';

/// Lightweight offline action queue backed by SQLite.
///
/// Usage (enqueue):
///   OfflineQueue.instance.push(url: AppConstants.sendMartMessage, body: {...});
///
/// Flushing happens automatically when connectivity is restored.
/// Each queued action carries an idempotency key so server-side replay is safe.
class OfflineQueue extends GetxService {
  static OfflineQueue get instance => Get.find<OfflineQueue>();

  Database? _db;
  StreamSubscription<List<ConnectivityResult>>? _connectivitySub;
  bool _isFlushing = false;

  @override
  void onInit() {
    super.onInit();
    _initDb();
    _connectivitySub = Connectivity().onConnectivityChanged.listen(_onConnectivityChanged);
  }

  @override
  void onClose() {
    _connectivitySub?.cancel();
    _db?.close();
    super.onClose();
  }

  Future<void> _initDb() async {
    final dbPath = p.join(await getDatabasesPath(), 'vito_offline_queue.db');
    _db = await openDatabase(
      dbPath,
      version: 1,
      onCreate: (db, version) async {
        await db.execute('''
          CREATE TABLE offline_actions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            url TEXT NOT NULL,
            body TEXT NOT NULL,
            idempotency_key TEXT NOT NULL UNIQUE,
            created_at INTEGER NOT NULL
          )
        ''');
      },
    );
  }

  /// Push an action to the queue. [url] and [body] mirror the args to [ApiClient.postData].
  /// Returns the idempotency key that will be sent with the request when flushed.
  Future<String> push({required String url, required Map<String, dynamic> body}) async {
    await _ensureDb();
    final key = _generateKey();
    await _db!.insert(
      'offline_actions',
      {
        'url': url,
        'body': jsonEncode(body),
        'idempotency_key': key,
        'created_at': DateTime.now().millisecondsSinceEpoch,
      },
      conflictAlgorithm: ConflictAlgorithm.ignore,
    );
    return key;
  }

  /// Flush all pending actions in FIFO order. Each is sent with its idempotency key
  /// so server-side replays are safe even if the app crashed mid-flush.
  Future<void> flush() async {
    if (_isFlushing) return;
    await _ensureDb();

    final rows = await _db!.query('offline_actions', orderBy: 'id ASC');
    if (rows.isEmpty) return;

    _isFlushing = true;
    try {
      final apiClient = Get.find<dynamic>(tag: 'ApiClient') as dynamic;
      for (final row in rows) {
        final url = row['url'] as String;
        final body = jsonDecode(row['body'] as String) as Map<String, dynamic>;
        final key = row['idempotency_key'] as String;

        try {
          final response = await apiClient.postData(url, body, idempotencyKey: key);
          if (response.statusCode == 200 || response.statusCode == 201) {
            await _db!.delete('offline_actions', where: 'idempotency_key = ?', whereArgs: [key]);
          }
        } catch (_) {
          // Leave in queue to retry on next flush.
        }
      }
    } finally {
      _isFlushing = false;
    }
  }

  void _onConnectivityChanged(List<ConnectivityResult> results) {
    final isOnline = results.any((r) => r != ConnectivityResult.none);
    if (isOnline) flush();
  }

  Future<void> _ensureDb() async {
    if (_db == null) await _initDb();
    var attempts = 0;
    while (_db == null && attempts++ < 20) {
      await Future.delayed(const Duration(milliseconds: 50));
    }
  }

  /// Generates a 32-hex-char random key for idempotency.
  static String _generateKey() {
    final rng = Random.secure();
    final bytes = List.generate(16, (_) => rng.nextInt(256));
    return bytes.map((b) => b.toRadixString(16).padLeft(2, '0')).join();
  }

  /// Generate a key without storing it — for one-off POST idempotency headers.
  static String generateIdempotencyKey() => _generateKey();
}
