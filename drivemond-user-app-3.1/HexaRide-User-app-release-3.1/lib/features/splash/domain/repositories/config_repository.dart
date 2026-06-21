import 'package:get/get.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:ride_sharing_user_app/data/api_client.dart';
import 'package:ride_sharing_user_app/features/splash/domain/repositories/config_repository_interface.dart';
import 'package:ride_sharing_user_app/util/app_constants.dart';
import 'package:shared_preferences/shared_preferences.dart';

class ConfigRepository implements ConfigRepositoryInterface{
  final ApiClient apiClient;
  final SharedPreferences sharedPreferences;
  const ConfigRepository({required this.apiClient, required this.sharedPreferences});

  @override
  Future<Response> getConfigData() {
    return apiClient.getData(AppConstants.configUri);
  }

  @override
  Future<bool> initSharedData() {
    if(!sharedPreferences.containsKey(AppConstants.theme)) {
      return sharedPreferences.setBool(AppConstants.theme, false);
    }
    if(!sharedPreferences.containsKey(AppConstants.countryCode)) {
      return sharedPreferences.setString(AppConstants.countryCode, AppConstants.languages[0].countryCode);
    }

    if(!sharedPreferences.containsKey(AppConstants.intro)) {
      sharedPreferences.setBool(AppConstants.intro, true);
    }
    return Future.value(true);
  }

  @override
  Future<bool> removeSharedData() async {
    // Clear the session (token + address) without wiping the saved language,
    // theme or intro flags, and reset the in-memory/secure token so a 401 does
    // not leave a stale token behind that re-triggers 401 on the next request.
    await Get.find<FlutterSecureStorage>().delete(key: AppConstants.token);
    await sharedPreferences.remove(AppConstants.token);
    await sharedPreferences.remove(AppConstants.userAddress);
    apiClient.clearToken();
    return true;
  }

  @override
  void disableIntro() {
    sharedPreferences.setBool(AppConstants.intro, false);
  }

  @override
  bool? showIntro() {
    if(!sharedPreferences.containsKey(AppConstants.intro)) {
      sharedPreferences.setBool(AppConstants.intro, true);
    }
    return sharedPreferences.getBool(AppConstants.intro);

  }

  @override
  bool haveOngoingRides(){
    return sharedPreferences.getBool(AppConstants.haveOngoingRides) ?? false;
  }

  @override
  void saveOngoingRides(bool value) {
    sharedPreferences.setBool(AppConstants.haveOngoingRides, value);
  }

}