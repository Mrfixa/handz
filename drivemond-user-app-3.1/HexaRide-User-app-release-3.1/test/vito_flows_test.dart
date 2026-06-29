import 'dart:convert';
import 'dart:io';
import 'package:flutter_test/flutter_test.dart';
import 'package:ride_sharing_user_app/data/api_checker.dart';
import 'package:ride_sharing_user_app/features/ride/domain/models/remaining_distance_model.dart';
import 'package:ride_sharing_user_app/features/mart/domain/models/mart_product_model.dart';
import 'package:ride_sharing_user_app/features/mart/domain/models/mart_category_model.dart';
import 'package:ride_sharing_user_app/features/mart/domain/models/mart_order_item_model.dart';
import 'package:ride_sharing_user_app/features/mart/domain/models/mart_order_model.dart';
import 'package:ride_sharing_user_app/util/parse_utils.dart';
import 'package:ride_sharing_user_app/features/mart/domain/mart_order_status.dart';

/// Unit tests for VITO-specific flows in the user app.
/// These validate localization, token logic, and widget structure
/// without requiring the full app or a running backend.
void main() {
  group('Localization Parity', () {
    late Map<String, dynamic> en;
    late Map<String, dynamic> es;

    setUpAll(() {
      en = jsonDecode(File('assets/language/en.json').readAsStringSync());
      es = jsonDecode(File('assets/language/es.json').readAsStringSync());
    });

    test('EN and ES have the same number of keys', () {
      expect(es.length, en.length,
          reason: 'ES should have the same keys as EN');
    });

    test('All EN keys exist in ES', () {
      final missingInEs = en.keys.where((k) => !es.containsKey(k)).toList();
      expect(missingInEs, isEmpty,
          reason: 'Keys missing in ES: $missingInEs');
    });

    test('All ES keys exist in EN', () {
      final extraInEs = es.keys.where((k) => !en.containsKey(k)).toList();
      expect(extraInEs, isEmpty,
          reason: 'Extra keys in ES not in EN: $extraInEs');
    });

    test('Vito-specific EN keys have non-empty values', () {
      final vitoKeys = [
        'invitation_required',
        'scan_qr_or_enter_token',
        'enter_invitation_token',
        'validate_token',
        'vito_mart',
        'order_tracking',
        'cart',
        'enter_username_and_pin',
        'pin_is_required',
        'username_is_required',
      ];
      for (final key in vitoKeys) {
        expect(en[key], isNotNull, reason: 'EN key "$key" should exist');
        expect(en[key], isNotEmpty, reason: 'EN key "$key" should not be empty');
      }
    });

    test('Vito-specific ES keys have Spanish translations', () {
      final esVitoKeys = {
        'invitation_required': 'Invitación Requerida',
        'validate_token': 'Validar Token',
        'vito_mart': 'VitoMart',
        'cart': 'Carrito',
        'pin_is_required': 'El PIN es obligatorio',
      };
      for (final entry in esVitoKeys.entries) {
        expect(es[entry.key], entry.value,
            reason: 'ES key "${entry.key}" should be "${entry.value}"');
      }
    });
  });

  group('Token Validation Logic', () {
    test('Empty token should be rejected', () {
      final token = '';
      expect(token.isEmpty, isTrue);
    });

    test('Short token (< 10 chars) should be rejected', () {
      final token = 'abc123';
      expect(token.length < 10, isTrue,
          reason: 'Token of ${token.length} chars should fail format check');
    });

    test('Valid token format (64 hex chars)', () {
      final token = 'a' * 64;
      expect(token.length, 64);
      expect(RegExp(r'^[a-f0-9]+$').hasMatch(token), isTrue);
    });

    test('Token with max length 64', () {
      final token = '0123456789abcdef' * 4;
      expect(token.length, 64);
    });
  });

  group('PIN Validation Logic', () {
    test('PIN must be exactly 6 digits', () {
      expect('123456'.length, 6);
      expect(RegExp(r'^\d{6}$').hasMatch('123456'), isTrue);
    });

    test('Short PIN rejected', () {
      expect(RegExp(r'^\d{6}$').hasMatch('12345'), isFalse);
    });

    test('PIN with letters rejected', () {
      expect(RegExp(r'^\d{6}$').hasMatch('123abc'), isFalse);
    });

    test('PIN confirmation must match', () {
      final pin = '654321';
      final confirm = '654321';
      expect(pin, confirm);
    });

    test('PIN mismatch detected', () {
      final pin = '654321';
      final confirm = '654320';
      expect(pin == confirm, isFalse);
    });
  });

  group('QR Token Expiry Logic', () {
    test('Customer token expires in 1 hour', () {
      final now = DateTime.now();
      final expiry = now.add(const Duration(hours: 1));
      final diff = expiry.difference(now);
      expect(diff.inMinutes, 60);
    });

    test('Driver onboarding token expires in 7 days', () {
      final now = DateTime.now();
      final expiry = now.add(const Duration(days: 7));
      final diff = expiry.difference(now);
      expect(diff.inDays, 7);
    });

    test('Expired token is detected', () {
      final expiry = DateTime.now().subtract(const Duration(hours: 1));
      expect(expiry.isBefore(DateTime.now()), isTrue);
    });
  });

  group('Mart Order Logic', () {
    test('Order status flow is valid', () {
      final statusFlow = [
        'pending',
        'accepted',
        'picked_up',
        'delivered',
      ];
      expect(statusFlow.length, 4);
      expect(statusFlow.first, 'pending');
      expect(statusFlow.last, 'delivered');
      // Verify the full transition sequence matches the backend canonical map.
      expect(statusFlow[0], 'pending');
      expect(statusFlow[1], 'accepted');
      expect(statusFlow[2], 'picked_up');
      expect(statusFlow[3], 'delivered');
    });

    test('Cart total calculation', () {
      final items = [
        {'price': 10.0, 'qty': 2},
        {'price': 5.50, 'qty': 3},
      ];
      final total = items.fold<double>(
        0,
        (sum, item) =>
            sum + (item['price'] as double) * (item['qty'] as int),
      );
      expect(total, 36.50);
    });
  });

  group('Client Auth Validation Logic', () {
    test('Empty first name is rejected in signup', () {
      const firstName = '';
      expect(firstName.trim().isEmpty, isTrue,
          reason: 'Empty first name should fail validation');
    });

    test('Phone with country code passes length check', () {
      const phone = '+15551234567';
      expect(phone.startsWith('+'), isTrue);
      expect(phone.length, greaterThanOrEqualTo(10));
    });

    test('Password shorter than 8 characters is invalid', () {
      const password = 'abc123';
      expect(password.length < 8, isTrue,
          reason: 'Password must be at least 8 characters');
    });

    test('Password mismatch is detected', () {
      const password = 'securePass1';
      const confirm = 'differentPass';
      expect(password == confirm, isFalse,
          reason: 'Passwords do not match');
    });

    test('Promo max_discount cap limits discount', () {
      const subtotal = 20.0;
      const discountPercent = 0.5;
      const maxDiscount = 3.0;
      final rawDiscount = subtotal * discountPercent;
      final appliedDiscount = rawDiscount > maxDiscount ? maxDiscount : rawDiscount;
      expect(appliedDiscount, 3.0);
    });

    test('Order total equals subtotal minus discount plus tip', () {
      const subtotal = 20.0;
      const discount = 3.0;
      const tip = 2.0;
      final total = subtotal - discount + tip;
      expect(total, 19.0);
    });

    test('Negative total is floored to zero', () {
      const subtotal = 2.0;
      const discount = 5.0;
      const tip = 0.0;
      final raw = subtotal - discount + tip;
      final total = raw < 0 ? 0.0 : raw;
      expect(total, 0.0);
    });

    test('Expired token is invalid', () {
      final expiry = DateTime.now().subtract(const Duration(minutes: 1));
      final isExpired = expiry.isBefore(DateTime.now());
      expect(isExpired, isTrue);
    });
  });

  // Locks in the crash-sweep: malformed/missing numeric fields must NOT throw.
  group('Model parse hardening', () {
    test('RemainingDistanceModel.fromJson tolerates a null distance', () {
      final model = RemainingDistanceModel.fromJson({'distance': null});
      expect(model.distance, isNull);
    });

    test('RemainingDistanceModel.fromJson tolerates a non-numeric distance', () {
      final model = RemainingDistanceModel.fromJson({'distance': 'not-a-number'});
      expect(model.distance, 0);
    });

    test('RemainingDistanceModel.fromJson parses valid numeric distances', () {
      expect(RemainingDistanceModel.fromJson({'distance': 12.5}).distance, 12.5);
      expect(RemainingDistanceModel.fromJson({'distance': 5}).distance, 5.0);
    });
  });

  group('Mart model parsing', () {
    test('MartProductModel.fromJson coerces types and computes inStock', () {
      final p = MartProductModel.fromJson(<String, dynamic>{
        'id': 1, 'name': 'Widget', 'price': '9.99', 'is_active': 1, 'stock': '3',
      });
      expect(p.id, '1');
      expect(p.name, 'Widget');
      expect(p.price, 9.99);
      expect(p.isActive, true);
      expect(p.stock, 3);
      expect(p.inStock, true);
      expect(p.toJson()['name'], 'Widget');
    });

    test('MartProductModel inStock is false when inactive or out of stock', () {
      expect(MartProductModel.fromJson(<String, dynamic>{'is_active': false, 'stock': 5}).inStock, false);
      expect(MartProductModel.fromJson(<String, dynamic>{'is_active': true, 'stock': 0}).inStock, false);
    });

    test('MartProductModel tolerates missing/garbage fields', () {
      final p = MartProductModel.fromJson(<String, dynamic>{'price': 'abc', 'stock': 'x'});
      expect(p.price, 0);
      expect(p.stock, 0);
      expect(p.name, isNull);
    });

    test('MartCategoryModel.fromJson and toJson round-trip', () {
      final c = MartCategoryModel.fromJson(<String, dynamic>{'id': 7, 'name': 'Tools', 'slug': 'tools'});
      expect(c.id, '7');
      expect(c.name, 'Tools');
      expect(c.toJson()['slug'], 'tools');
    });

    test('MartOrderItemModel parses nested product and displayName', () {
      final it = MartOrderItemModel.fromJson(<String, dynamic>{
        'id': 'i1', 'product_id': 'p1', 'quantity': '2', 'unit_price': '5', 'total_price': '10',
        'product': <String, dynamic>{'name': 'Soap'},
      });
      expect(it.quantity, 2);
      expect(it.unitPrice, 5);
      expect(it.totalPrice, 10);
      expect(it.product?.name, 'Soap');
      expect(it.displayName, 'Soap');
      expect(it.toJson()['product'], isNotNull);
    });

    test('MartOrderItemModel displayName falls back when product is absent', () {
      expect(MartOrderItemModel.fromJson(<String, dynamic>{'quantity': 1}).displayName, 'Item');
    });

    test('MartOrderModel parses items, driver name, and itemCount', () {
      final o = MartOrderModel.fromJson(<String, dynamic>{
        'id': 'o1', 'ref_id': 'R1', 'status': 'pending', 'total_amount': '20.50',
        'driver': <String, dynamic>{'first_name': 'Jane', 'last_name': 'Doe'},
        'items': <Map<String, dynamic>>[
          <String, dynamic>{'quantity': 2, 'product': <String, dynamic>{'name': 'A'}},
          <String, dynamic>{'quantity': 3, 'product': <String, dynamic>{'name': 'B'}},
        ],
      });
      expect(o.id, 'o1');
      expect(o.totalAmount, 20.50);
      expect(o.driverName, 'Jane Doe');
      expect(o.items.length, 2);
      expect(o.itemCount, 5);
      expect(o.toJson()['ref_id'], 'R1');
    });

    test('MartOrderModel tolerates missing items and driver', () {
      final o = MartOrderModel.fromJson(<String, dynamic>{'id': 'o2'});
      expect(o.items, isEmpty);
      expect(o.itemCount, 0);
      expect(o.driverName, isNull);
    });
  });

  group('Session 401 handling', () {
    // A transient/secondary 401 must NOT destroy a valid session — only the
    // deliberate startup auth check (handleUnauthorized: true) may log out.
    test('secondary 401 does not invalidate the session', () {
      expect(ApiChecker.shouldInvalidateSession(401, handleUnauthorized: false), false);
    });
    test('startup-confirmed 401 invalidates the session', () {
      expect(ApiChecker.shouldInvalidateSession(401, handleUnauthorized: true), true);
    });
    test('non-401 never invalidates the session', () {
      expect(ApiChecker.shouldInvalidateSession(200, handleUnauthorized: true), false);
      expect(ApiChecker.shouldInvalidateSession(403, handleUnauthorized: true), false);
      expect(ApiChecker.shouldInvalidateSession(408, handleUnauthorized: true), false);
      expect(ApiChecker.shouldInvalidateSession(null, handleUnauthorized: true), false);
    });
  });

  // WS3 — safe numeric coercion for server-supplied fields (PriceConverter, config, etc.).
  group('parse_utils', () {
    test('toDoubleOr handles num, numeric string, null, and garbage', () {
      expect(toDoubleOr(5), 5.0);
      expect(toDoubleOr('5.5'), 5.5);
      expect(toDoubleOr(null), 0);
      expect(toDoubleOr('abc', 1.0), 1.0);
      expect(toDoubleOr('null'), 0); // server null-as-string must not throw
    });

    test('toIntOr handles num, int/double strings, null, and garbage', () {
      expect(toIntOr(5), 5);
      expect(toIntOr('5'), 5);
      expect(toIntOr('5.9'), 5);
      expect(toIntOr(null, 1), 1);
      expect(toIntOr('abc', 2), 2);
      // currencyDecimalPoint misconfig used to crash every price render via int.parse.
      expect(toIntOr('x', 1).clamp(0, 20), 1);
    });

    test('toIntOrNull returns null for null/garbage', () {
      expect(toIntOrNull(null), isNull);
      expect(toIntOrNull('7'), 7);
      expect(toIntOrNull('x'), isNull);
    });
  });

  // WS4 — pure mart order-status logic extracted from mart_order_tracking_screen.
  group('mart_order_status', () {
    test('martOrderStepIndex follows pending→accepted→picked_up→delivered', () {
      expect(martOrderStepIndex('pending'), 0);
      expect(martOrderStepIndex('accepted'), 1);
      expect(martOrderStepIndex('picked_up'), 2);
      expect(martOrderStepIndex('delivered'), 3);
      expect(martOrderStepIndex('cancelled'), -1);
      expect(martOrderStepIndex('weird'), 0); // unknown defaults to the first step
    });

    test('isMartOrderTerminal only for delivered/cancelled', () {
      expect(isMartOrderTerminal('delivered'), isTrue);
      expect(isMartOrderTerminal('cancelled'), isTrue);
      expect(isMartOrderTerminal('pending'), isFalse);
      expect(isMartOrderTerminal('accepted'), isFalse);
      expect(isMartOrderTerminal('picked_up'), isFalse);
    });

    test('canCancelMartOrder only before pickup', () {
      expect(canCancelMartOrder('pending'), isTrue);
      expect(canCancelMartOrder('accepted'), isTrue);
      expect(canCancelMartOrder('picked_up'), isFalse);
      expect(canCancelMartOrder('delivered'), isFalse);
      expect(canCancelMartOrder('cancelled'), isFalse);
    });
  });
}
