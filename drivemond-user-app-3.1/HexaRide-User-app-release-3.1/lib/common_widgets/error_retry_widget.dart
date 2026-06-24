import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
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
  const ErrorRetryWidget({
    super.key,
    required this.onRetry,
    this.message,
    this.icon = Icons.cloud_off_rounded,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(Dimensions.paddingSizeLarge),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          // Animated error icon with container
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.error.withValues(alpha: 0.1),
              shape: BoxShape.circle,
            ),
            child: Icon(
              icon,
              size: 56,
              color: Theme.of(context).colorScheme.error,
            ),
          ),
          const SizedBox(height: Dimensions.paddingSizeLarge),
          Text(
            message ?? 'something_went_wrong'.tr,
            textAlign: TextAlign.center,
            style: textMedium.copyWith(
              color: Theme.of(context).textTheme.bodyLarge?.color,
              fontSize: Dimensions.fontSizeDefault,
            ),
          ),
          const SizedBox(height: Dimensions.paddingSizeSmall),
          Text(
            'please_try_again'.tr,
            textAlign: TextAlign.center,
            style: textRegular.copyWith(
              color: Theme.of(context).hintColor,
              fontSize: Dimensions.fontSizeSmall,
            ),
          ),
          const SizedBox(height: Dimensions.paddingSizeLarge),
          ButtonWidget(
            buttonText: 'retry'.tr,
            icon: Icons.refresh,
            width: 180,
            radius: 50,
            onPressed: () {
              HapticFeedback.mediumImpact();
              onRetry();
            },
          ),
        ]),
      ),
    );
  }
}
