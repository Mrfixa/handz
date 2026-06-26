import 'dart:convert';
import 'dart:io';
import 'package:get/get_connect/http/src/response/response.dart';
import 'package:image_picker/image_picker.dart';
import 'package:ride_sharing_user_app/data/api_client.dart';
import 'package:ride_sharing_user_app/features/mart/domain/repositories/mart_repository_interface.dart';
import 'package:ride_sharing_user_app/util/app_constants.dart';

class MartRepository implements MartRepositoryInterface {
  final ApiClient apiClient;
  MartRepository({required this.apiClient});

  @override
  Future<Response> getPendingOrders({int limit = 20}) async {
    return await apiClient.getData('${AppConstants.martPendingOrders}?limit=$limit');
  }

  @override
  Future<Response> getMyOrders({int limit = 20}) async {
    return await apiClient.getData('${AppConstants.martMyOrders}?limit=$limit');
  }

  @override
  Future<Response> getOrderDetails(String id) async {
    return await apiClient.getData('${AppConstants.martOrderDetails}$id');
  }

  @override
  Future<Response> acceptOrder(String orderId) async {
    return await apiClient.postData(AppConstants.martAcceptOrder, {'order_id': orderId});
  }

  @override
  Future<Response> updateStatus(String orderId, String status, {String? reason, double? driverLat, double? driverLng, String? idempotencyKey}) async {
    return await apiClient.putData(
      AppConstants.martUpdateStatus,
      {
        'order_id': orderId,
        'status': status,
        if (reason != null && reason.isNotEmpty) 'reason': reason,
        if (driverLat != null) 'driver_lat': driverLat,
        if (driverLng != null) 'driver_lng': driverLng,
      },
      headers: idempotencyKey != null ? {'Idempotency-Key': idempotencyKey} : null,
    );
  }

  @override
  Future<Response> uploadDeliveryProof(String orderId, {String? photoPath, List<int>? signatureBytes, String? idempotencyKey}) async {
    final fields = <String, String>{'order_id': orderId};
    if (signatureBytes != null) {
      fields['signature_base64'] = base64Encode(signatureBytes);
    }
    final files = <MultipartBody>[];
    if (photoPath != null && File(photoPath).existsSync()) {
      files.add(MultipartBody('delivery_photo', XFile(photoPath)));
    }
    return await apiClient.postMultipartData(
      AppConstants.martUploadProof,
      fields,
      files,
      null,
      <MultipartDocument>[],
      headers: idempotencyKey != null ? {'Idempotency-Key': idempotencyKey} : null,
    );
  }

  @override
  Future add(value) => throw UnimplementedError();
  @override
  Future delete(int id) => throw UnimplementedError();
  @override
  Future get(String id) => throw UnimplementedError();
  @override
  Future getList({int? offset = 1}) => throw UnimplementedError();
  @override
  Future update(Map<String, dynamic> body, int id) => throw UnimplementedError();
}
