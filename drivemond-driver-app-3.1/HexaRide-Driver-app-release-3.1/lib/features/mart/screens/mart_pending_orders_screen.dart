import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/features/mart/controllers/mart_controller.dart';
import 'package:ride_sharing_user_app/features/mart/screens/mart_delivery_screen.dart';
import 'package:ride_sharing_user_app/features/mart/screens/mart_order_history_screen.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';
import 'package:ride_sharing_user_app/common_widgets/app_bar_widget.dart';
import 'package:ride_sharing_user_app/helper/display_helper.dart';

class MartPendingOrdersScreen extends StatefulWidget {
  const MartPendingOrdersScreen({super.key});

  @override
  State<MartPendingOrdersScreen> createState() => _MartPendingOrdersScreenState();
}

class _MartPendingOrdersScreenState extends State<MartPendingOrdersScreen> {
  Timer? _refreshTimer;

  MartController get _martController => Get.find<MartController>();

  @override
  void initState() {
    super.initState();
    _martController.getPendingOrders();
    _refreshTimer = Timer.periodic(const Duration(seconds: 15), (_) {
      if (mounted) _martController.getPendingOrders(notify: false);
    });
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    super.dispose();
  }

  Future<void> _acceptOrder(String orderId) async {
    HapticFeedback.mediumImpact();
    final success = await _martController.acceptOrder(orderId);
    if (success && mounted) {
      Get.off(() => MartDeliveryScreen(orderId: orderId));
    } else if (mounted) {
      showCustomSnackBar('order_not_available'.tr);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBarWidget(title: 'pending_mart_orders'.tr, regularAppbar: true, showLogo: true),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => Get.to(() => const MartOrderHistoryScreen()),
        backgroundColor: Theme.of(context).primaryColor,
        icon: const Icon(Icons.receipt_long, color: Colors.white),
        label: Text('mart_order_history'.tr, style: const TextStyle(color: Colors.white)),
      ),
      body: GetBuilder<MartController>(
        builder: (controller) {
          if (controller.isLoading && controller.pendingOrders.isEmpty) {
            return Center(child: CircularProgressIndicator(color: Theme.of(context).primaryColor));
          }
          return RefreshIndicator(
            onRefresh: () => controller.getPendingOrders(),
            color: Theme.of(context).primaryColor,
            child: controller.pendingOrders.isEmpty
                ? ListView(
                    children: [
                      SizedBox(height: Get.height * 0.3),
                      Icon(Icons.storefront_outlined, size: 64, color: Theme.of(context).hintColor),
                      const SizedBox(height: Dimensions.paddingSizeDefault),
                      Text(
                        'no_pending_orders'.tr,
                        textAlign: TextAlign.center,
                        style: textMedium.copyWith(color: Theme.of(context).hintColor),
                      ),
                      const SizedBox(height: Dimensions.paddingSizeSmall),
                      Text(
                        'new_orders_appear_here'.tr,
                        textAlign: TextAlign.center,
                        style: textRegular.copyWith(
                          color: Theme.of(context).hintColor,
                          fontSize: Dimensions.fontSizeSmall,
                        ),
                      ),
                    ],
                  )
                : ListView.separated(
                    padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                    itemCount: controller.pendingOrders.length,
                    separatorBuilder: (_, __) => const SizedBox(height: Dimensions.paddingSizeSmall),
                    itemBuilder: (context, index) => _buildOrderCard(
                      context,
                      controller.pendingOrders[index].toJson(),
                      controller.isActionLoading,
                    ),
                  ),
          );
        },
      ),
    );
  }

  Widget _buildOrderCard(BuildContext context, Map<String, dynamic> order, bool isActionLoading) {
    final orderId = order['id'] ?? '';
    final refId = order['ref_id'] ?? '';
    final address = order['delivery_address'] ?? '';
    final total = order['total_amount'] ?? 0;
    final items = (order['items'] as List?) ?? [];
    final itemCount = items.length;

    return Card(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.radiusDefault)),
      child: Padding(
        padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('#$refId', style: textBold.copyWith(fontSize: Dimensions.fontSizeDefault)),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: Dimensions.paddingSizeSmall,
                    vertical: Dimensions.paddingSizeExtraSmall,
                  ),
                  decoration: BoxDecoration(
                    color: Theme.of(context).primaryColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(Dimensions.radiusSmall),
                  ),
                  child: Text(
                    'pending'.tr,
                    style: textMedium.copyWith(
                      color: Theme.of(context).primaryColor,
                      fontSize: Dimensions.fontSizeSmall,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: Dimensions.paddingSizeSmall),
            Row(children: [
              Icon(Icons.location_on_outlined, size: 16, color: Theme.of(context).hintColor),
              const SizedBox(width: 4),
              Expanded(
                child: Text(address,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: textRegular.copyWith(
                    fontSize: Dimensions.fontSizeSmall,
                    color: Theme.of(context).hintColor,
                  ),
                ),
              ),
            ]),
            const SizedBox(height: Dimensions.paddingSizeSmall),
            Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
              Text('$itemCount ${'items_ordered'.tr}',
                  style: textRegular.copyWith(fontSize: Dimensions.fontSizeSmall)),
              Text('\$$total',
                  style: textBold.copyWith(
                    color: Theme.of(context).primaryColor,
                    fontSize: Dimensions.fontSizeDefault,
                  )),
            ]),
            const SizedBox(height: Dimensions.paddingSizeDefault),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: isActionLoading ? null : () => _acceptOrder(orderId),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Theme.of(context).primaryColor,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
                  ),
                ),
                child: Text('accept_order'.tr,
                    style: textBold.copyWith(color: Theme.of(context).colorScheme.onPrimary)),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
