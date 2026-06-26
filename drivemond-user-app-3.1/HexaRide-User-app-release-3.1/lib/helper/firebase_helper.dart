
import 'dart:io';

import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/data/api_client.dart';
import 'package:ride_sharing_user_app/util/app_constants.dart';

class FirebaseHelper {

  void listenForTokenRefresh() {
    FirebaseMessaging.instance.onTokenRefresh.listen((newToken) async {
      try {
        await Get.find<ApiClient>().postData(
          AppConstants.fcmTokenUpdate,
          {"_method": "put", "fcm_token": newToken},
        );
      } catch (_) {
        // Token refresh failures must not crash the app.
      }
    });
  }

  void subscribeFirebaseTopic() async{
    if (Platform.isIOS) {
      String? apnsToken = await FirebaseMessaging.instance.getAPNSToken();
      if (apnsToken != null) {
        await FirebaseMessaging.instance.subscribeToTopic('customer_maintenance_mode_on');
        await FirebaseMessaging.instance.subscribeToTopic('customer_maintenance_mode_off');
        await FirebaseMessaging.instance.subscribeToTopic('customers_send_notification');
      } else {
        await Future<void>.delayed(
          const Duration(
            seconds: 3,
          ),
        );
        apnsToken = await FirebaseMessaging.instance.getAPNSToken();
        if (apnsToken != null) {
          await FirebaseMessaging.instance.subscribeToTopic('customer_maintenance_mode_on');
          await FirebaseMessaging.instance.subscribeToTopic('customer_maintenance_mode_off');
          await FirebaseMessaging.instance.subscribeToTopic('customers_send_notification');
        }
      }
    } else {
      await FirebaseMessaging.instance.subscribeToTopic('customer_maintenance_mode_on');
      await FirebaseMessaging.instance.subscribeToTopic('customer_maintenance_mode_off');
      await FirebaseMessaging.instance.subscribeToTopic('customers_send_notification');
    }
  }
}