abstract class MartServiceInterface {
  Future<dynamic> getPendingOrders({int limit});
  Future<dynamic> getMyOrders({int limit});
  Future<dynamic> getOrderDetails(String id);
  Future<dynamic> acceptOrder(String orderId);
  Future<dynamic> updateStatus(String orderId, String status, {String? reason, double? driverLat, double? driverLng, String? idempotencyKey});
  Future<dynamic> uploadDeliveryProof(String orderId, {String? photoPath, List<int>? signatureBytes, String? idempotencyKey});
}
