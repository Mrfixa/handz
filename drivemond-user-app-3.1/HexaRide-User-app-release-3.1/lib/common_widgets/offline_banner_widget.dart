import 'dart:async';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

class ConnectivityController extends GetxController implements GetxService {
  bool isOnline = true;
  bool _isInitialized = false;
  StreamSubscription<List<ConnectivityResult>>? _sub;

  @override
  void onInit() {
    super.onInit();
    _checkInitialConnectivity();
    _sub = Connectivity().onConnectivityChanged.listen(_handleConnectivityChange);
  }

  Future<void> _checkInitialConnectivity() async {
    final results = await Connectivity().checkConnectivity();
    _handleConnectivityChange(results);
    _isInitialized = true;
  }

  void _handleConnectivityChange(List<ConnectivityResult> results) {
    final connected = results.contains(ConnectivityResult.wifi) ||
        results.contains(ConnectivityResult.mobile) ||
        results.contains(ConnectivityResult.ethernet);
    if (isOnline != connected) {
      isOnline = connected;
      update();
    }
  }

  @override
  void onClose() {
    _sub?.cancel();
    super.onClose();
  }
}

class OfflineBannerWidget extends StatelessWidget {
  final Widget child;
  const OfflineBannerWidget({super.key, required this.child});

  @override
  Widget build(BuildContext context) {
    if (!Get.isRegistered<ConnectivityController>()) {
      Get.put(ConnectivityController(), permanent: true);
    }
    return GetBuilder<ConnectivityController>(builder: (ctrl) {
      return Column(children: [
        AnimatedContainer(
          duration: const Duration(milliseconds: 300),
          height: ctrl.isOnline ? 0 : null,
          child: ctrl.isOnline
              ? const SizedBox.shrink()
              : Material(
                  color: Colors.transparent,
                  child: Container(
                    width: double.infinity,
                    color: Colors.red.shade700,
                    padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 16),
                    child: SafeArea(
                      bottom: false,
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(Icons.wifi_off, color: Colors.white, size: 16),
                          const SizedBox(width: 8),
                          Text(
                            'you_are_offline'.tr,
                            textAlign: TextAlign.center,
                            style: textRegular.copyWith(color: Colors.white, fontSize: 13),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
        ),
        Expanded(child: child),
      ]);
    });
  }
}
