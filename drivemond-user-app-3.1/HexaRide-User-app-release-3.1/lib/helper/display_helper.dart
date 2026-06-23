import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/helper/responsive_helper.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/images.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

void customPrint(String message) {
  if(kDebugMode) {
    print(message);
  }
}

/// Shows a snackbar with consistent styling. 
/// [isError] determines the icon (red X for errors, green check for success).
/// [seconds] controls auto-dismiss duration (default 3, longer for warnings).
/// [subMessage] optional secondary text shown below the main message.
void showCustomSnackBar(String message, {bool isError = true, int seconds = 3, String? subMessage}) {
  // Don't stack snackbars - dismiss any existing one first
  if (Get.isSnackbarOpen) {
    Get.closeCurrentSnackbar();
  }
  
  final isDark = Get.isDarkMode;
  final backgroundColor = isError 
      ? const Color(0xFFE53935)  // Material Red 600
      : const Color(0xFF43A047);  // Material Green 600
  
  Get.showSnackbar(GetSnackBar(
    dismissDirection: DismissDirection.horizontal,
    margin: const EdgeInsets.all(Dimensions.paddingSizeSmall).copyWith(
      right: ResponsiveHelper.isDesktop ? Get.context!.width * 0.7 : Dimensions.paddingSizeSmall,
      bottom: 100, // Lift above bottom navigation
    ),
    padding: const EdgeInsets.symmetric(
      horizontal: Dimensions.paddingSizeDefault,
      vertical: Dimensions.paddingSizeSmall,
    ),
    duration: Duration(seconds: seconds),
    backgroundColor: backgroundColor,
    borderRadius: Dimensions.paddingSizeDefault,
    snackStyle: SnackStyle.FLOATING, // Float above bottom sheets
    messageText: Row(children: [
      Icon(
        isError ? Icons.error_outline : Icons.check_circle_outline,
        color: Colors.white,
        size: 22,
      ),
      const SizedBox(width: Dimensions.paddingSize),
      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisSize: MainAxisSize.min, children: [
        Text(
          message,
          style: textMedium.copyWith(color: Colors.white, fontSize: Dimensions.fontSizeSmall),
        ),
        if (subMessage != null) ...[
          const SizedBox(height: 2),
          Text(
            subMessage,
            style: textRegular.copyWith(color: Colors.white.withValues(alpha: 0.85), fontSize: 12),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ])),
    ]),
  ));
}

/// Info snackbar variant - blue background for informational messages
void showInfoSnackBar(String message, {int seconds = 4, String? subMessage}) {
  if (Get.isSnackbarOpen) {
    Get.closeCurrentSnackbar();
  }
  
  Get.showSnackbar(GetSnackBar(
    dismissDirection: DismissDirection.horizontal,
    margin: const EdgeInsets.all(Dimensions.paddingSizeSmall).copyWith(
      right: ResponsiveHelper.isDesktop ? Get.context!.width * 0.7 : Dimensions.paddingSizeSmall,
      bottom: 100,
    ),
    padding: const EdgeInsets.symmetric(
      horizontal: Dimensions.paddingSizeDefault,
      vertical: Dimensions.paddingSizeSmall,
    ),
    duration: Duration(seconds: seconds),
    backgroundColor: const Color(0xFF1E88E5), // Material Blue 600
    borderRadius: Dimensions.paddingSizeDefault,
    snackStyle: SnackStyle.FLOATING,
    messageText: Row(children: [
      const Icon(Icons.info_outline, color: Colors.white, size: 22),
      const SizedBox(width: Dimensions.paddingSize),
      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisSize: MainAxisSize.min, children: [
        Text(
          message,
          style: textMedium.copyWith(color: Colors.white, fontSize: Dimensions.fontSizeSmall),
        ),
        if (subMessage != null) ...[
          const SizedBox(height: 2),
          Text(
            subMessage,
            style: textRegular.copyWith(color: Colors.white.withValues(alpha: 0.85), fontSize: 12),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ])),
    ]),
  ));
}

/// Warning snackbar variant - amber background for warnings
void showWarningSnackBar(String message, {int seconds = 5, String? subMessage}) {
  if (Get.isSnackbarOpen) {
    Get.closeCurrentSnackbar();
  }
  
  Get.showSnackbar(GetSnackBar(
    dismissDirection: DismissDirection.horizontal,
    margin: const EdgeInsets.all(Dimensions.paddingSizeSmall).copyWith(
      right: ResponsiveHelper.isDesktop ? Get.context!.width * 0.7 : Dimensions.paddingSizeSmall,
      bottom: 100,
    ),
    padding: const EdgeInsets.symmetric(
      horizontal: Dimensions.paddingSizeDefault,
      vertical: Dimensions.paddingSizeSmall,
    ),
    duration: Duration(seconds: seconds),
    backgroundColor: const Color(0xFFFFA726), // Material Orange 400
    borderRadius: Dimensions.paddingSizeDefault,
    snackStyle: SnackStyle.FLOATING,
    messageText: Row(children: [
      const Icon(Icons.warning_amber_rounded, color: Colors.white, size: 22),
      const SizedBox(width: Dimensions.paddingSize),
      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisSize: MainAxisSize.min, children: [
        Text(
          message,
          style: textMedium.copyWith(color: Colors.white, fontSize: Dimensions.fontSizeSmall),
        ),
        if (subMessage != null) ...[
          const SizedBox(height: 2),
          Text(
            subMessage,
            style: textRegular.copyWith(color: Colors.white.withValues(alpha: 0.85), fontSize: 12),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ])),
    ]),
  ));
}
