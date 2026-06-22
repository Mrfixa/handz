import 'package:ride_sharing_user_app/features/mart/domain/models/mart_order_item_model.dart';

class MartOrderModel {
  final String? id;
  final String? refId;
  final String? status;
  final double totalAmount;
  final double tipAmount;
  final double discountAmount;
  final String? promoCode;
  final String? paymentStatus;
  final String? paymentMethod;
  final String? deliveryAddress;
  final String? signatureImage;
  final String? deliveryPhoto;
  final String? notes;
  final String? driverId;
  final String? driverName;
  final String? createdAt;
  final List<MartOrderItemModel> items;

  MartOrderModel({
    this.id,
    this.refId,
    this.status,
    this.totalAmount = 0,
    this.tipAmount = 0,
    this.discountAmount = 0,
    this.promoCode,
    this.paymentStatus,
    this.paymentMethod,
    this.deliveryAddress,
    this.signatureImage,
    this.deliveryPhoto,
    this.notes,
    this.driverId,
    this.driverName,
    this.createdAt,
    this.items = const [],
  });

  factory MartOrderModel.fromJson(Map<String, dynamic> json) {
    final driver = json['driver'];
    return MartOrderModel(
      id: json['id']?.toString(),
      refId: json['ref_id']?.toString(),
      status: json['status']?.toString(),
      totalAmount: double.tryParse(json['total_amount']?.toString() ?? '') ?? 0,
      tipAmount: double.tryParse(json['tip_amount']?.toString() ?? '') ?? 0,
      discountAmount: double.tryParse(json['discount_amount']?.toString() ?? '') ?? 0,
      promoCode: json['promo_code']?.toString(),
      paymentStatus: json['payment_status']?.toString(),
      paymentMethod: json['payment_method']?.toString(),
      deliveryAddress: json['delivery_address']?.toString(),
      signatureImage: json['signature_image']?.toString(),
      deliveryPhoto: json['delivery_photo']?.toString(),
      notes: json['notes']?.toString(),
      driverId: json['driver_id']?.toString(),
      driverName: driver is Map<String, dynamic>
          ? '${driver['first_name'] ?? ''} ${driver['last_name'] ?? ''}'.trim()
          : null,
      createdAt: json['created_at']?.toString(),
      items: json['items'] is List
          ? (json['items'] as List)
              .whereType<Map<String, dynamic>>()
              .map(MartOrderItemModel.fromJson)
              .toList()
          : const [],
    );
  }

  int get itemCount => items.fold(0, (sum, i) => sum + i.quantity);
}
