import 'package:ride_sharing_user_app/interface/repository_interface.dart';

abstract class MartRepositoryInterface implements RepositoryInterface {
  Future<dynamic> getProducts({String? category, String? search, int limit});
  Future<dynamic> getCategories();
  Future<dynamic> getProductDetails(String id);
  Future<dynamic> getOrders({int limit});
  Future<dynamic> getOrderDetails(String id);
  Future<dynamic> cancelOrder(String id);
  Future<dynamic> reviewOrder(String id, int rating, String? comment);
}
