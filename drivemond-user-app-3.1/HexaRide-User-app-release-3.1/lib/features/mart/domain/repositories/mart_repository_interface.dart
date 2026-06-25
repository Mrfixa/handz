import 'package:get/get_connect/http/src/response/response.dart';
import 'package:ride_sharing_user_app/interface/repository_interface.dart';

abstract class MartRepositoryInterface implements RepositoryInterface {
  Future<Response> getProducts({String? category, String? search, int limit});
  Future<Response> getCategories();
  Future<Response> getProductDetails(String id);
  Future<Response> getOrders({int limit});
  Future<Response> getOrderDetails(String id);
  Future<Response> cancelOrder(String id);
  Future<Response> reviewOrder(String id, int rating, String? comment);
  Future<Response> createOrder(Map<String, dynamic> orderData);
}
