import 'dart:async';
import 'package:flutter/material.dart';
import 'package:pin_code_fields/pin_code_fields.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

class VitoPinField extends StatelessWidget {
  final TextEditingController controller;
  final FocusNode? focusNode;
  final StreamController<ErrorAnimationType>? errorController;
  final ValueChanged<String>? onChanged;
  final ValueChanged<String>? onCompleted;
  final bool autoFocus;

  const VitoPinField({
    super.key,
    required this.controller,
    this.focusNode,
    this.errorController,
    this.onChanged,
    this.onCompleted,
    this.autoFocus = false,
  });

  @override
  Widget build(BuildContext context) {
    return PinCodeTextField(
      appContext: context,
      length: 6,
      obscureText: true,
      keyboardType: TextInputType.number,
      animationType: AnimationType.fade,
      enableActiveFill: true,
      autoFocus: autoFocus,
      focusNode: focusNode,
      controller: controller,
      errorAnimationController: errorController,
      pinTheme: PinTheme(
        shape: PinCodeFieldShape.box,
        fieldHeight: 48,
        fieldWidth: 44,
        borderWidth: 1,
        borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
        selectedColor: Theme.of(context).primaryColor.withValues(alpha: 0.5),
        selectedFillColor: Theme.of(context).cardColor,
        inactiveFillColor: Theme.of(context).cardColor,
        inactiveColor: Theme.of(context).hintColor.withValues(alpha: 0.3),
        activeColor: Theme.of(context).primaryColor.withValues(alpha: 0.5),
        activeFillColor: Theme.of(context).cardColor,
        errorBorderColor: Colors.red,
      ),
      animationDuration: const Duration(milliseconds: 300),
      backgroundColor: Colors.transparent,
      textStyle: textSemiBold,
      pastedTextStyle: textRegular.copyWith(
        color: Theme.of(context).textTheme.bodyMedium?.color,
      ),
      onChanged: (value) {
        controller.text = value;
        onChanged?.call(value);
      },
      onCompleted: onCompleted,
      beforeTextPaste: (text) => true,
    );
  }
}
