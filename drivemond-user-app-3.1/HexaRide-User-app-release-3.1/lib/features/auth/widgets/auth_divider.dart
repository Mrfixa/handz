import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

/// A horizontal "label between two rules" divider used on the auth screens
/// (e.g. the "or" separator between primary and alternate sign-in actions).
class AuthDivider extends StatelessWidget {
  final String label;
  const AuthDivider({super.key, required this.label});

  @override
  Widget build(BuildContext context) {
    final Color lineColor = Theme.of(context).hintColor.withValues(alpha: 0.25);
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeSmall),
      child: Row(
        children: [
          Expanded(child: Divider(color: lineColor, thickness: 1)),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
            child: Text(
              label.tr,
              style: textRegular.copyWith(
                color: Theme.of(context).hintColor,
                fontSize: Dimensions.fontSizeSmall,
              ),
            ),
          ),
          Expanded(child: Divider(color: lineColor, thickness: 1)),
        ],
      ),
    );
  }
}
