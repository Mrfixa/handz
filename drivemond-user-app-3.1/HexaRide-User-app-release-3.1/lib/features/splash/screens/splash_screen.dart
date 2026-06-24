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
  bool _isConnected = true;

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

    _isConnected = results.any((result) => 
        result == ConnectivityResult.wifi || 
        result == ConnectivityResult.mobile ||
        result == ConnectivityResult.ethernet);

    // Remove any existing snackbars
    ScaffoldMessenger.of(Get.context!).removeCurrentSnackBar();

    if (_isConnected) {
      LoginHelper().handleIncomingLinks(widget.notificationData);
    } else {
      // Show retry option for no connectivity
      showDialog(
        context: Get.context!,
        barrierDismissible: false,
        builder: (ctx) => AlertDialog(
          title: Text('no_connection'.tr),
          content: Text('please_check_internet_and_try_again'.tr),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.pop(ctx);
                setState(() => _hasCheckedConnectivity = false);
                _checkConnectivity();
              },
              child: Text('retry'.tr),
            ),
          ],
        ),
      );
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
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Lottie.asset(
                      'assets/lottie/splash_3d.json',
                      controller: _lottieController,
                      width: 200,
                      height: 200,
                    ),
                    const SizedBox(height: 24),
                    // Loading indicator with localized text
                    if (!_isConnected) ...[
                      const CircularProgressIndicator(),
                      const SizedBox(height: 16),
                      Text(
                        'checking_connection'.tr,
                        style: const TextStyle(color: Colors.white70),
                      ),
                    ],
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}
