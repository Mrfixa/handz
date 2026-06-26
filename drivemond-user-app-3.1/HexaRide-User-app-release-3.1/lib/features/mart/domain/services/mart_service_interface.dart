abstract class MartServiceInterface {
  Future<dynamic> getProducts({String? category, String? search, int limit});
  Future<dynamic> getCategories();
  Future<dynamic> getProductDetails(String id);
  Future<dynamic> getOrders({int limit});
  Future<dynamic> getOrderDetails(String id);
  Future<dynamic> cancelOrder(String id);
  Future<dynamic> reviewOrder(String id, int rating, String? comment);
  Future<dynamic> createOrder(Map<String, dynamic> orderData, {String? idempotencyKey});
}
