import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/common_widgets/button_widget.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

/// Consistent failure state: icon + message + a retry CTA. Replaces ad-hoc or
/// blank error UI so every load failure looks and behaves the same.
class ErrorRetryWidget extends StatelessWidget {
  final VoidCallback onRetry;
  final String? message;
  final IconData icon;
  const ErrorRetryWidget({super.key, required this.onRetry, this.message, this.icon = Icons.cloud_off_rounded});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(Dimensions.paddingSizeLarge),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Icon(icon, size: 56, color: Theme.of(context).hintColor),
          const SizedBox(height: Dimensions.paddingSizeDefault),
          Text(
            message ?? 'something_went_wrong'.tr,
            textAlign: TextAlign.center,
            style: textRegular.copyWith(color: Theme.of(context).hintColor, fontSize: Dimensions.fontSizeDefault),
          ),
          const SizedBox(height: Dimensions.paddingSizeLarge),
          ButtonWidget(
            buttonText: 'retry'.tr,
            icon: Icons.refresh,
            width: 180,
            radius: 50,
            onPressed: onRetry,
          ),
        ]),
      ),
    );
  }
}
