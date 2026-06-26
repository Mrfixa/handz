import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:ride_sharing_user_app/data/api_checker.dart';
import 'package:ride_sharing_user_app/data/offline_queue.dart';
import 'package:ride_sharing_user_app/features/mart/domain/models/mart_category_model.dart';
import 'package:ride_sharing_user_app/features/mart/domain/models/mart_order_model.dart';
import 'package:ride_sharing_user_app/features/mart/domain/models/mart_product_model.dart';
import 'package:ride_sharing_user_app/features/mart/domain/services/mart_service_interface.dart';
import 'package:ride_sharing_user_app/helper/display_helper.dart';

class MartController extends GetxController implements GetxService {
  final MartServiceInterface martServiceInterface;
  MartController({required this.martServiceInterface});

  bool isLoading = false;
  bool isActionLoading = false;

  // Idempotency key for order creation; regenerated after each failed attempt
  // so that retries are treated as new requests by the backend middleware.
  String _orderIdempotencyKey = OfflineQueue.generateIdempotencyKey();

  List<MartProductModel> products = [];
  List<MartCategoryModel> categories = [];
  String selectedCategory = 'all';

  List<MartOrderModel> orders = [];
  MartOrderModel? currentOrder;
  MartProductModel? productDetails;

  // Cart state - persisted to SharedPreferences
  static const String _cartKey = 'mart_cart_items';
  List<Map<String, dynamic>> _cartItems = [];
  List<Map<String, dynamic>> get cartItems => _cartItems;

  @override
  void onInit() {
    super.onInit();
    _loadCartFromStorage();
    getCategories();
  }

  Future<void> _loadCartFromStorage() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final cartJson = prefs.getString(_cartKey);
      if (cartJson != null) {
        final List<dynamic> decoded = jsonDecode(cartJson);
        _cartItems = decoded.cast<Map<String, dynamic>>();
        update();
      }
    } catch (e) {
      debugPrint('Failed to load cart from storage: $e');
      _cartItems = [];
    }
  }

  Future<void> _saveCartToStorage() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_cartKey, jsonEncode(_cartItems));
    } catch (e) {
      debugPrint('Failed to save cart to storage: $e');
    }
  }

  // Cart total calculation
  double get cartTotal {
    double total = 0.0;
    for (final item in _cartItems) {
      final price = double.tryParse(item['price']?.toString() ?? '0') ?? 0.0;
      final qty = item['quantity'] as int? ?? 1;
      if (price > 0 && qty > 0) {
        total += price * qty;
      }
    }
    return total.clamp(0.0, 999999.99);
  }

  int get cartItemCount => _cartItems.length;

  /// Adds [product] to the cart. Returns true if the item was successfully added
  /// (or quantity incremented), false if blocked by a stock constraint.
  /// Shows the appropriate error snackbar internally when returning false.
  bool addToCart(Map<String, dynamic> product, {int quantity = 1}) {
    final stock = int.tryParse(product['stock']?.toString() ?? '') ?? 0;

    // FIX 3: reject immediately if stock is 0
    if (stock <= 0) {
      showCustomSnackBar('out_of_stock'.tr);
      return false;
    }

    final existingIndex = _cartItems.indexWhere(
      (item) => item['id'] == product['id'],
    );

    if (existingIndex >= 0) {
      final existing = Map<String, dynamic>.from(_cartItems[existingIndex]);
      final newQty = (existing['quantity'] as int? ?? 1) + quantity;
      // FIX 3: cap at available stock
      if (newQty > stock) {
        existing['quantity'] = stock;
        _cartItems[existingIndex] = existing;
        _saveCartToStorage();
        update();
        showCustomSnackBar('stock_limit_exceeded'.tr);
        return false;
      }
      existing['quantity'] = newQty;
      _cartItems[existingIndex] = existing;
    } else {
      _cartItems.add({
        'id': product['id'],
        'name': product['name'],
        'price': product['price'],
        'image': product['image'],
        'quantity': quantity,
      });
    }

    _saveCartToStorage();
    update();
    return true;
  }

  void updateCartItemQuantity(String productId, int quantity) {
    if (quantity <= 0) {
      removeFromCart(productId);
      return;
    }

    final index = _cartItems.indexWhere((item) => item['id'] == productId);
    if (index >= 0) {
      final item = Map<String, dynamic>.from(_cartItems[index]);
      item['quantity'] = quantity;
      _cartItems[index] = item;
      _saveCartToStorage();
      update();
    }
  }

  void removeFromCart(String productId) {
    _cartItems.removeWhere((item) => item['id'] == productId);
    _saveCartToStorage();
    update();
  }

  void clearCart() {
    _cartItems = [];
    _saveCartToStorage();
    update();
  }

  // Get categories as string list for UI
  List<String> get categoryList {
    final list = ['all'];
    for (final cat in categories) {
      if (cat.name != null && cat.name!.isNotEmpty) {
        list.add(cat.name!);
      }
    }
    return list.isEmpty ? ['all', 'food', 'drinks', 'snacks', 'essentials'] : list;
  }

  /// Helper to extract a list payload that may be a plain list or a Laravel
  /// paginator ({data: {data: [...]}}).
  List<dynamic> _extractList(dynamic body) {
    final data = body is Map ? body['data'] : null;
    if (data is List) return data;
    if (data is Map && data['data'] is List) return data['data'];
    return const [];
  }

  Future<void> getProducts({String? category, String? search, bool notify = true}) async {
    isLoading = true;
    if (notify) update();
    final response = await martServiceInterface.getProducts(category: category ?? selectedCategory, search: search);
    if (response.statusCode == 200) {
      products = _extractList(response.body)
          .whereType<Map<String, dynamic>>()
          .map(MartProductModel.fromJson)
          .toList();
    } else {
      ApiChecker.checkApi(response);
    }
    isLoading = false;
    if (notify) update();
  }

  Future<void> getCategories({bool notify = true}) async {
    final response = await martServiceInterface.getCategories();
    if (response.statusCode == 200) {
      categories = _extractList(response.body)
          .whereType<Map<String, dynamic>>()
          .map(MartCategoryModel.fromJson)
          .toList();
    }
    if (notify) update();
  }

  void setCategory(String category) {
    selectedCategory = category;
    update();
    getProducts(category: category);
  }

  Future<MartProductModel?> getProductDetails(String id) async {
    isLoading = true;
    update();
    productDetails = null;
    final response = await martServiceInterface.getProductDetails(id);
    if (response.statusCode == 200 && response.body['data'] != null) {
      productDetails = MartProductModel.fromJson(response.body['data']);
    } else {
      ApiChecker.checkApi(response);
    }
    isLoading = false;
    update();
    return productDetails;
  }

  Future<void> getOrders({bool notify = true}) async {
    isLoading = true;
    if (notify) update();
    final response = await martServiceInterface.getOrders();
    if (response.statusCode == 200) {
      orders = _extractList(response.body)
          .whereType<Map<String, dynamic>>()
          .map(MartOrderModel.fromJson)
          .toList();
    } else {
      ApiChecker.checkApi(response);
    }
    isLoading = false;
    if (notify) update();
  }

  Future<MartOrderModel?> getOrderDetails(String id, {bool notify = true}) async {
    final response = await martServiceInterface.getOrderDetails(id);
    if (response.statusCode == 200 && response.body['data'] != null) {
      currentOrder = MartOrderModel.fromJson(response.body['data']);
      if (notify) update();
      return currentOrder;
    }
    ApiChecker.checkApi(response);
    return null;
  }

  Future<bool> cancelOrder(String id) async {
    isActionLoading = true;
    update();
    final response = await martServiceInterface.cancelOrder(id);
    isActionLoading = false;
    update();
    if (response.statusCode == 200) {
      return true;
    }
    ApiChecker.checkApi(response);
    return false;
  }

  Future<bool> reviewOrder(String id, int rating, String? comment) async {
    isActionLoading = true;
    update();
    final response = await martServiceInterface.reviewOrder(id, rating, comment);
    isActionLoading = false;
    update();
    if (response.statusCode == 200) {
      return true;
    }
    ApiChecker.checkApi(response);
    return false;
  }

  /// Creates a mart order through the service layer.
  /// Returns a tuple of (success, orderId, serverTotal, errorMessage)
  Future<({bool success, String? orderId, double serverTotal, String? error})> createOrder({
    required List<Map<String, dynamic>> items,
    required String deliveryAddress,
    String? notes,
    required String paymentMethod,
    double? deliveryLat,
    double? deliveryLng,
    double? tipAmount,
    String? promoCode,
  }) async {
    isActionLoading = true;
    update();

    final body = <String, dynamic>{
      'items': items,
      'delivery_address': deliveryAddress,
      if (notes != null && notes.isNotEmpty) 'notes': notes,
      'payment_method': paymentMethod,
      if (deliveryLat != null) 'delivery_lat': deliveryLat,
      if (deliveryLng != null) 'delivery_lng': deliveryLng,
      if (tipAmount != null && tipAmount > 0) 'tip_amount': tipAmount,
      if (promoCode != null && promoCode.isNotEmpty) 'promo_code': promoCode,
    };

    final response = await martServiceInterface.createOrder(body, idempotencyKey: _orderIdempotencyKey);
    isActionLoading = false;
    update();

    if (response.statusCode == 200 || response.statusCode == 201) {
      final data = response.body['data'];
      final orderId = (data?['id'] ?? data?['order_id'] ?? '').toString();
      if (orderId.isEmpty) {
        // Rotate the key so a retry is a new request.
        _orderIdempotencyKey = OfflineQueue.generateIdempotencyKey();
        return (success: false, orderId: null, serverTotal: 0.0, error: 'invalid_order_response'.tr);
      }
      // FIX 1: extract the backend-computed total so callers never use a client-computed value.
      final serverTotal = double.tryParse(data?['total_amount']?.toString() ?? '') ?? 0.0;
      // Success: rotate so the next distinct order uses a fresh key.
      _orderIdempotencyKey = OfflineQueue.generateIdempotencyKey();
      return (success: true, orderId: orderId, serverTotal: serverTotal, error: null);
    }

    // Rotate the key on failure so a retry is not treated as a duplicate.
    _orderIdempotencyKey = OfflineQueue.generateIdempotencyKey();

    // Extract error message
    String? errorMsg;
    try {
      final errors = response.body['errors'];
      if (errors is List && errors.isNotEmpty) {
        final first = errors.first;
        if (first is Map && first['message'] != null) {
          errorMsg = first['message'].toString();
        }
      }
      if (errorMsg == null && response.body['message'] is String) {
        errorMsg = response.body['message'];
      }
    } catch (_) {}
    return (success: false, orderId: null, serverTotal: 0.0, error: errorMsg ?? 'order_failed'.tr);
  }
}
