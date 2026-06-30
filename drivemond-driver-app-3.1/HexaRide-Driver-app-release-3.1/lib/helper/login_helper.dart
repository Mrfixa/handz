import 'dart:io';
import 'package:app_links/app_links.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/features/auth/controllers/auth_controller.dart';
import 'package:ride_sharing_user_app/features/realtime_location_trac/screens/live_location_screen.dart';
import 'package:ride_sharing_user_app/localization/language_selection_screen.dart';
import 'package:ride_sharing_user_app/localization/localization_controller.dart';
import 'package:ride_sharing_user_app/features/auth/screens/sign_in_screen.dart';
import 'package:ride_sharing_user_app/features/dashboard/screens/dashboard_screen.dart';
import 'package:ride_sharing_user_app/features/location/controllers/location_controller.dart';
import 'package:ride_sharing_user_app/features/location/screens/access_location_screen.dart';
import 'package:ride_sharing_user_app/features/maintainance_mode/screens/maintainance_screen.dart';
import 'package:ride_sharing_user_app/features/out_of_zone/controllers/out_of_zone_controller.dart';
import 'package:ride_sharing_user_app/features/profile/controllers/profile_controller.dart';
import 'package:ride_sharing_user_app/features/splash/controllers/splash_controller.dart';
import 'package:ride_sharing_user_app/features/splash/domain/models/config_model.dart';
import 'package:ride_sharing_user_app/features/splash/screens/app_version_warning_screen.dart';
import 'package:ride_sharing_user_app/helper/firebase_helper.dart';
import 'package:ride_sharing_user_app/helper/notification_helper.dart';
import 'package:ride_sharing_user_app/util/app_constants.dart';
import 'pusher_helper.dart';

class LoginHelper{

  LoginHelper();

  void handleIncomingLinks(Map<String, dynamic>? notificationData) async {

    final appLinks = AppLinks();
    final Uri? initialLink = await appLinks.getInitialLink();
    final String? deepLinkPath = initialLink?.path;

    Get.find<SplashController>().getConfigData()
        .timeout(const Duration(seconds: 15), onTimeout: () => false)
        .then((isSuccess) => _routeAfterConfig(notificationData, deepLinkPath))
        .catchError((_) => _routeAfterConfig(notificationData, deepLinkPath));
  }

  // Routes once config has loaded (or failed/timed out) — never hangs on splash,
  // and language selection is the first screen when none is saved.
  void _routeAfterConfig(Map<String, dynamic>? notificationData, String? deepLinkPath){
    if (_isForceUpdate(Get.find<SplashController>().config)) {
      Get.offAll(() => const AppVersionWarningScreen());
    } else if (deepLinkPath != null && deepLinkPath.isNotEmpty) {
      Get.offAll(() => LiveLocationScreen(trackingUrl: deepLinkPath));
    } else if (!Get.find<LocalizationController>().haveLocalLanguageCode()) {
      Get.offAll(() => LanguageSelectionScreen(notificationData: notificationData));
    } else {
      // Fetch registration lookups (identity types, vehicle categories, brands, fuel types)
      Get.find<AuthController>().fetchRegistrationLookups();
      checkLoginRoutes(notificationData);
    }
  }

  void checkLoginRoutes(Map<String,dynamic>? notificationData){
    FirebaseHelper().subscribeFirebaseTopic();
    if(Get.find<AuthController>().getUserToken().isNotEmpty){
      PusherHelper.initializePusher();
    }

    Future.delayed(const Duration(milliseconds: 1000), () {
      if(Get.find<AuthController>().isLoggedIn()){
        if(Get.find<AuthController>().getZoneId() == ''){
          Get.offAll(()=> const AccessLocationScreen());

        }else{
          try { Get.find<OutOfZoneController>().getZoneList(); } catch (_) {}
          Get.find<ProfileController>().getProfileInfo()
              .timeout(const Duration(seconds: 15), onTimeout: () => Response(statusCode: 408))
              .then((value){
            if(value.statusCode == 200){
              try {
                PusherHelper().driverTripRequestSubscribe(value.body['data']['id']);
              } catch (_) {}
              // Route to the dashboard regardless of whether the location fetch
              // succeeds, so a denied/failed GPS read never strands the driver.
              Get.find<LocationController>().getCurrentLocation().whenComplete((){
                if(notificationData != null){
                  NotificationHelper.notificationToRoute(notificationData, formSplash: true, userName: notificationData['user_name']);
                }else{
                  Get.offAll(()=> const DashboardScreen());
                }
              });
            } else if(value.statusCode == 401){
              // Confirmed dead session on the dedicated startup auth check — the
              // ONLY place that deliberately clears the session.
              Get.find<SplashController>().removeSharedData();
              checkLoginMedium();
            } else {
              // Any other failure (timeout/offline/5xx): never eject the driver.
              Get.offAll(()=> const DashboardScreen());
            }
          }).catchError((_){
            Get.offAll(()=> const DashboardScreen());
          });

        }

      }else{
        final maintenance = Get.find<SplashController>().config?.maintenanceMode;
        if(maintenance != null &&
            maintenance.maintenanceStatus == 1 &&
            maintenance.selectedMaintenanceSystem?.driverApp == 1
        ){
          Get.offAll(() => const MaintenanceScreen());
        }else{
          checkLoginMedium();
        }
      }
    });
  }

  bool _isForceUpdate(ConfigModel? config) {
    double minimumVersion = Platform.isAndroid
        ? config?.androidAppMinimumVersion ?? 0
        : Platform.isIOS
        ? config?.iosAppMinimumVersion ?? 0
        : 0;

    return minimumVersion > 0 && minimumVersion > AppConstants.appVersion;
  }

  static void checkLoginMedium(){
    Get.offAll(()=> const SignInScreen());
  }
}
