import 'package:get/get.dart';
import 'package:ride_sharing_user_app/features/splash/controllers/splash_controller.dart';
import 'package:ride_sharing_user_app/helper/display_helper.dart';
import 'package:ride_sharing_user_app/helper/login_helper.dart';
import 'error_response.dart';

class ApiChecker {
  /// Pure predicate: a 401 invalidates the session ONLY when the caller is the
  /// deliberate auth check (handleUnauthorized: true). A transient/secondary
  /// 401 must never destroy a valid session. Unit-testable in isolation.
  static bool shouldInvalidateSession(int? statusCode, {required bool handleUnauthorized}) {
    return statusCode == 401 && handleUnauthorized;
  }

  static void checkApi(Response response, {bool handleUnauthorized = false}) {
    if(response.statusCode == 401) {
      // Only the startup auth check (or an explicit caller) may clear the
      // session. Every other 401 is swallowed so the caller keeps its UI state
      // — this prevents spurious "bounced back to login" on background 401s.
      if(shouldInvalidateSession(response.statusCode, handleUnauthorized: handleUnauthorized)) {
        Get.find<SplashController>().removeSharedData();
        LoginHelper.checkLoginMedium();
      }

    }else if(response.statusCode == 403){
      ErrorResponse errorResponse;
      errorResponse = ErrorResponse.fromJson(response.body);
      if(errorResponse.errors != null && errorResponse.errors!.isNotEmpty){
        showCustomSnackBar(errorResponse.errors![0].message!);
      }else{
        showCustomSnackBar(response.body['message']!);
      }

    } else if (response.statusCode == 429) {
      showCustomSnackBar('too_many_requests'.tr);
    } else {
      showCustomSnackBar(response.statusText ?? 'something_went_wrong'.tr);
    }
  }
}
