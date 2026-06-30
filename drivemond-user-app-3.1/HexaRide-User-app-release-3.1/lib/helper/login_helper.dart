import 'dart:async';
import 'dart:io';
import 'package:app_links/app_links.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/features/auth/controllers/auth_controller.dart';
import 'package:ride_sharing_user_app/features/auth/screens/sign_in_screen.dart';
import 'package:ride_sharing_user_app/features/auth/screens/token_gate_screen.dart';
import 'package:ride_sharing_user_app/features/auth/screens/otp_log_in_screen.dart';
import 'package:ride_sharing_user_app/features/auth/domain/enums/verification_from_enum.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:ride_sharing_user_app/features/dashboard/screens/dashboard_screen.dart';
import 'package:ride_sharing_user_app/features/location/controllers/location_controller.dart';
import 'package:ride_sharing_user_app/features/location/view/access_location_screen.dart';
import 'package:ride_sharing_user_app/features/maintainance_mode/maintainance_screen.dart';
import 'package:ride_sharing_user_app/features/realtime_location_trac/screens/live_location_screen.dart';
import 'package:ride_sharing_user_app/features/onboard/screens/onboarding_screen.dart';
import 'package:ride_sharing_user_app/features/payment/controllers/payment_controller.dart';
import 'package:ride_sharing_user_app/features/profile/controllers/profile_controller.dart';
import 'package:ride_sharing_user_app/features/refund_request/controllers/refund_request_controller.dart';
import 'package:ride_sharing_user_app/features/splash/controllers/config_controller.dart';
import 'package:ride_sharing_user_app/features/splash/domain/models/config_model.dart';
import 'package:ride_sharing_user_app/features/splash/screens/app_version_warning_screen.dart';
import 'package:ride_sharing_user_app/features/trip/controllers/trip_controller.dart';
import 'package:ride_sharing_user_app/helper/firebase_helper.dart';
import 'package:ride_sharing_user_app/helper/notification_helper.dart';
import 'package:ride_sharing_user_app/helper/pusher_helper.dart';
import 'package:ride_sharing_user_app/localization/language_selection_screen.dart';
import 'package:ride_sharing_user_app/localization/localization_controller.dart';
import 'package:ride_sharing_user_app/util/app_constants.dart';

class LoginHelper{

  void handleIncomingLinks(Map<String,dynamic>? notificationData) async{
    Get.find<TripController>().getRideCancellationReasonList();
    Get.find<TripController>().getParcelCancellationReasonList();
    Get.find<RefundRequestController>().getParcelRefundReasonList();
    Get.find<PaymentController>().getPaymentGetWayList();
    FirebaseHelper().subscribeFirebaseTopic();
    FirebaseHelper().listenForTokenRefresh();
    String? path = await initDynamicLinks();

    // Check for deep link in notificationData (set by onGenerateRoute)
    final deepLink = notificationData?['deep_link'] as String?;
    if (deepLink != null && deepLink.isNotEmpty) {
      path = deepLink;
    }

    Get.find<ConfigController>().getConfigData()
        .timeout(const Duration(seconds: 15), onTimeout: () => false)
        .then((value){
      if(_isForceUpdate(Get.find<ConfigController>().config)) {
        Get.offAll(()=> const AppVersionWarningScreen());
      }else if(path != null && path.isNotEmpty){
        // Check if it's an auth deep link
        if (path == 'signup' || path == 'login') {
          _handleAuthDeepLink(path);
        } else {
          Get.offAll(()=> LiveLocationScreen(trackingUrl: path));
        }
      }else{
        route(notificationData);
      }
    }).catchError((_){
      // Never get stuck on splash if config fails to load.
      if(path != null && path.isNotEmpty){
        if (path == 'signup' || path == 'login') {
          _handleAuthDeepLink(path);
        } else {
          Get.offAll(()=> LiveLocationScreen(trackingUrl: path));
        }
      }else{
        route(notificationData);
      }
    });

  }

  void _handleAuthDeepLink(String path) {
    if (path == 'signup') {
      Get.offAll(() => const TokenGateScreen());
    } else if (path == 'login') {
      Get.offAll(() => const SignInScreen());
    }
  }

  Future<String?> initDynamicLinks() async {
    final appLinks = AppLinks();
    final uri = await appLinks.getInitialLink();
    String? path;
    if (uri != null) {
      path = uri.path;

    }else{
      path = null;
    }
    return path;

  }

  bool _isForceUpdate(ConfigModel? config) {
    double minimumVersion = Platform.isAndroid
        ? config?.androidAppMinimumVersion ?? 0
        : Platform.isIOS
        ? config?.iosAppMinimumVersion ?? 0
        : 0;

    return minimumVersion > 0 && minimumVersion > AppConstants.appVersion;
  }

  void route(Map<String,dynamic>? notificationData) async {

    if(Get.find<AuthController>().getUserToken().isNotEmpty){
      PusherHelper.initializePusher();
    }

    Future.delayed(const Duration(milliseconds: 100), () {
      // Language selection is the first screen for anyone without a saved language.
      if(!Get.find<LocalizationController>().haveLocalLanguageCode()){
        Get.offAll(()=> LanguageSelectionScreen(notificationData: notificationData));
        return;
      }
      if(Get.find<AuthController>().isLoggedIn()) {
        forLoginUserRoute(notificationData);
      }else{
        forNotLoginUserRoute(notificationData);
      }
    });

  }

  void forNotLoginUserRoute(Map<String,dynamic>? notificationData){
    final maintenance = Get.find<ConfigController>().config?.maintenanceMode;
    if(maintenance != null &&
        maintenance.maintenanceStatus == 1 &&
        maintenance.selectedMaintenanceSystem?.userApp == 1
    ){
      Get.offAll(() => const MaintenanceScreen());
    }else if (Get.find<ConfigController>().showIntro()) {
      Get.offAll(() => OnBoardingScreen(notificationData: notificationData));
    }else {
      checkLoginMedium();
    }
  }

  void forLoginUserRoute(Map<String,dynamic>? notificationData){
    if(notificationData != null) {
      NotificationHelper.notificationRouteCheck(notificationData, formSplash: true, userName: notificationData['user_name']);

    }else if(Get.find<LocationController>().getUserAddress() != null
        && Get.find<LocationController>().getUserAddress()!.address != null
        && Get.find<LocationController>().getUserAddress()!.address!.isNotEmpty) {

      Get.find<ProfileController>().getProfileInfo()
          .timeout(const Duration(seconds: 15), onTimeout: () => Response(statusCode: 408))
          .then((value) {
        if(value.statusCode == 200) {
          Get.find<AuthController>().updateToken();
          Get.find<AuthController>().remainingFindingRideTime();
          Get.offAll(()=> const DashboardScreen());
        } else if(value.statusCode == 401) {
          // Confirmed dead session on the dedicated startup auth check — this is
          // the ONLY place that deliberately clears the session.
          Get.find<ConfigController>().removeSharedData();
          Get.offAll(() => const SignInScreen());
        } else {
          // Any other failure (timeout/offline/5xx): never eject the user.
          Get.offAll(()=> const DashboardScreen());
        }
      }).catchError((_) {
        Get.offAll(()=> const DashboardScreen());
      });

    }else{
      Get.offAll(() => const AccessLocationScreen());
    }
  }

  // New users sign up with their phone number + SMS OTP (Twilio). After first
  // registration a flag is persisted so returning users go straight to sign-in
  // (which also offers "login with OTP").
  static void checkLoginMedium() async {
    final prefs = await SharedPreferences.getInstance();
    final hasRegistered = prefs.getBool('has_registered_before') ?? false;
    if (hasRegistered) {
      Get.offAll(() => const SignInScreen());
    } else {
      Get.offAll(() => const OtpLoginScreen(from: VerificationForm.signUp));
    }
  }

  static Future<void> markRegistered() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('has_registered_before', true);
  }

}