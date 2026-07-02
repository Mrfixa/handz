import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/common_widgets/app_bar_widget.dart';
import 'package:ride_sharing_user_app/common_widgets/button_widget.dart';
import 'package:ride_sharing_user_app/common_widgets/text_field_widget.dart';
import 'package:ride_sharing_user_app/common_widgets/vito_pin_field.dart';
import 'package:ride_sharing_user_app/features/auth/controllers/auth_controller.dart';
import 'package:ride_sharing_user_app/features/auth/widgets/text_field_title_widget.dart';
import 'package:ride_sharing_user_app/helper/display_helper.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';

/// Self-service PIN recovery: enter the username to receive an SMS OTP, then
/// enter the OTP and a new PIN. Backed by the forgot-pin endpoints.
class ForgotPinScreen extends StatefulWidget {
  const ForgotPinScreen({super.key});

  @override
  State<ForgotPinScreen> createState() => _ForgotPinScreenState();
}

class _ForgotPinScreenState extends State<ForgotPinScreen> {
  final TextEditingController _usernameController = TextEditingController();
  final TextEditingController _otpController = TextEditingController();
  final TextEditingController _newPinController = TextEditingController();
  final TextEditingController _confirmPinController = TextEditingController();
  final FocusNode _usernameNode = FocusNode();
  final FocusNode _otpNode = FocusNode();
  final FocusNode _newPinNode = FocusNode();
  final FocusNode _confirmPinNode = FocusNode();

  bool _otpSent = false;

  @override
  void dispose() {
    _usernameController.dispose();
    _otpController.dispose();
    _newPinController.dispose();
    _confirmPinController.dispose();
    _usernameNode.dispose();
    _otpNode.dispose();
    _newPinNode.dispose();
    _confirmPinNode.dispose();
    super.dispose();
  }

  bool _isSixDigits(String value) => RegExp(r'^\d{6}$').hasMatch(value);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBarWidget(title: 'forgot_pin'.tr, regularAppbar: true),
      body: SafeArea(
        child: GetBuilder<AuthController>(builder: (authController) {
          return Column(children: [
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  const SizedBox(height: Dimensions.paddingSizeDefault),
                  Text('forgot_pin_hint'.tr),
                  const SizedBox(height: Dimensions.paddingSizeDefault),

                  TextFieldTitleWidget(title: 'username'.tr, isRequired: true),
                  TextFieldWidget(
                    hintText: 'username'.tr,
                    controller: _usernameController,
                    focusNode: _usernameNode,
                    isEnabled: !_otpSent,
                    inputAction: TextInputAction.done,
                  ),

                  if (_otpSent) ...[
                    const SizedBox(height: Dimensions.paddingSizeDefault),
                    TextFieldTitleWidget(title: 'otp'.tr, isRequired: true),
                    VitoPinField(
                      controller: _otpController,
                      focusNode: _otpNode,
                    ),

                    const SizedBox(height: Dimensions.paddingSizeDefault),
                    TextFieldTitleWidget(title: 'new_pin'.tr, isRequired: true),
                    VitoPinField(
                      controller: _newPinController,
                      focusNode: _newPinNode,
                    ),

                    const SizedBox(height: Dimensions.paddingSizeDefault),
                    TextFieldTitleWidget(title: 'confirm_new_pin'.tr, isRequired: true),
                    VitoPinField(
                      controller: _confirmPinController,
                      focusNode: _confirmPinNode,
                    ),
                  ],
                ]),
              ),
            ),

            Padding(
              padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
              child: ButtonWidget(
                isLoading: authController.isLoading,
                radius: Dimensions.radiusExtraLarge,
                height: 52,
                buttonText: _otpSent ? 'reset_pin'.tr : 'send_otp'.tr,
                onPressed: () => _otpSent ? _submitReset(authController) : _submitSendOtp(authController),
              ),
            ),
          ]);
        }),
      ),
    );
  }

  void _submitSendOtp(AuthController authController) async {
    final String username = _usernameController.text.trim();
    if (username.isEmpty) {
      showCustomSnackBar('username_is_required'.tr);
      FocusScope.of(context).requestFocus(_usernameNode);
      return;
    }
    final bool sent = await authController.forgotPinSendOtp(username);
    if (sent) {
      setState(() => _otpSent = true);
    }
  }

  void _submitReset(AuthController authController) async {
    final String username = _usernameController.text.trim();
    final String otp = _otpController.text.trim();
    final String newPin = _newPinController.text.trim();
    final String confirmPin = _confirmPinController.text.trim();

    if (!_isSixDigits(otp)) {
      showCustomSnackBar('otp_must_be_6_digits'.tr);
      FocusScope.of(context).requestFocus(_otpNode);
    } else if (!_isSixDigits(newPin)) {
      showCustomSnackBar('pin_must_be_6_digits'.tr);
      FocusScope.of(context).requestFocus(_newPinNode);
    } else if (newPin != confirmPin) {
      showCustomSnackBar('pins_do_not_match'.tr);
      FocusScope.of(context).requestFocus(_confirmPinNode);
    } else {
      final bool success = await authController.resetPinWithOtp(username, otp, newPin);
      if (success) {
        Get.back();
      }
    }
  }
}
