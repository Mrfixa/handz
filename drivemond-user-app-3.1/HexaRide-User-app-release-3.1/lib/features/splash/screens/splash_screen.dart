import 'dart:async';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lottie/lottie.dart';
import 'package:ride_sharing_user_app/helper/login_helper.dart';
import 'package:ride_sharing_user_app/util/images.dart';
import 'package:ride_sharing_user_app/features/splash/controllers/config_controller.dart';

class SplashScreen extends StatefulWidget {
  final Map<String, dynamic>? notificationData;
  const SplashScreen({super.key, this.notificationData});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> with TickerProviderStateMixin {
  StreamSubscription<List<ConnectivityResult>>? _onConnectivityChanged;
  late AnimationController _fadeController;
  late Animation<double> _fadeAnimation;
  late AnimationController _lottieController;
  bool _hasCheckedConnectivity = false;

  @override
  void initState() {
    super.initState();

    _fadeController = AnimationController(vsync: this, duration: const Duration(seconds: 1));
    _fadeAnimation = Tween(begin: 0.0, end: 1.0).animate(_fadeController);

    _lottieController = AnimationController(vsync: this, duration: const Duration(seconds: 2));

    _fadeController.forward();
    _lottieController.repeat();

    Get.find<ConfigController>().initSharedData();

    _checkConnectivity();
  }

  Future<void> _checkConnectivity() async {
    // First check current connectivity status
    final connectivityResult = await Connectivity().checkConnectivity();
    _handleConnectivityResult(connectivityResult);

    // Then listen for changes
    _onConnectivityChanged = Connectivity().onConnectivityChanged.listen(_handleConnectivityResult);
  }

  void _handleConnectivityResult(List<ConnectivityResult> results) {
    if (_hasCheckedConnectivity) return; // Only run once on initial check
    _hasCheckedConnectivity = true;

    bool isConnected = results.any((result) => 
        result == ConnectivityResult.wifi || 
        result == ConnectivityResult.mobile ||
        result == ConnectivityResult.ethernet);

    Get.closeAllSnackbars();
    Get.snackbar(
      '',
      isConnected ? 'connected'.tr : 'no_connection'.tr,
      snackPosition: SnackPosition.BOTTOM,
      backgroundColor: isConnected ? Colors.green : Colors.red,
      colorText: Colors.white,
      duration: Duration(seconds: isConnected ? 3 : 6),
      titleText: const SizedBox.shrink(),
      messageText: Text(
        isConnected ? 'connected'.tr : 'no_connection'.tr,
        textAlign: TextAlign.center,
        style: const TextStyle(color: Colors.white),
      ),
    );

    if (isConnected) {
      LoginHelper().handleIncomingLinks(widget.notificationData);
    }
  }

  @override
  void dispose() {
    _fadeController.dispose();
    _lottieController.dispose();
    _onConnectivityChanged?.cancel();
    super.dispose();
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: AnimatedBuilder(
        animation: _fadeAnimation,
        builder: (context, child) {
          return Opacity(
            opacity: _fadeAnimation.value,
            child: Container(
              decoration: const BoxDecoration(
                image: DecorationImage(
                  image: AssetImage(Images.splashBackground),
                  fit: BoxFit.cover,
                ),
              ),
              child: Center(
                child: Lottie.asset(
                  'assets/lottie/splash_3d.json',
                  controller: _lottieController,
                  width: 200,
                  height: 200,
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}
