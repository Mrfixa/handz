import 'dart:async';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:pin_code_fields/pin_code_fields.dart';
import 'package:ride_sharing_user_app/common_widgets/button_widget.dart';
import 'package:ride_sharing_user_app/common_widgets/text_field_widget.dart';
import 'package:ride_sharing_user_app/common_widgets/vito_pin_field.dart';
import 'package:ride_sharing_user_app/features/auth/controllers/auth_controller.dart';
import 'package:ride_sharing_user_app/features/auth/screens/additional_sign_up_screen_2.dart';
import 'package:ride_sharing_user_app/features/auth/widgets/signup_appbar_widget.dart';
import 'package:ride_sharing_user_app/features/auth/widgets/text_field_title_widget.dart';
import 'package:ride_sharing_user_app/features/splash/controllers/splash_controller.dart';
import 'package:ride_sharing_user_app/helper/display_helper.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/images.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

class AdditionalSignUpScreen1 extends StatefulWidget {
  const AdditionalSignUpScreen1({super.key});

  @override
  State<AdditionalSignUpScreen1> createState() => _AdditionalSignUpScreen1State();
}

class _AdditionalSignUpScreen1State extends State<AdditionalSignUpScreen1> {
  final StreamController<ErrorAnimationType> _confirmPinErrorController =
      StreamController<ErrorAnimationType>.broadcast();

  @override
  void dispose() {
    _confirmPinErrorController.close();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).cardColor,
      body: SafeArea(
          child: GetBuilder<AuthController>(builder: (authController) {
            return Column(children: [
              const SignUpAppbarWidget(title: 'signup_as_a_driver', progressText: '2_of_3', enableBackButton: true),

              Expanded(child: SingleChildScrollView(
                  child: Column(children: [
                    const SizedBox(height: Dimensions.paddingSizeSignUp),

                    Text('provide_basic_info'.tr, style: textBold.copyWith(fontSize: 22)),
                    const SizedBox(height: Dimensions.paddingSizeSmall),

                    Text('enter_your_information'.tr, style: textRegular.copyWith(
                      color: Theme.of(context).textTheme.bodyMedium?.color?.withValues(alpha: 0.7),
                      fontSize: Dimensions.fontSizeSmall,
                    )),
                    const SizedBox(height: Dimensions.paddingSizeLarge),

                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        TextFieldTitleWidget(title: 'username'.tr, isRequired: true),

                        TextFieldWidget(
                          hintText: 'username'.tr,
                          inputType: TextInputType.text,
                          prefixIcon: Images.person,
                          controller: authController.usernameController,
                          focusNode: authController.usernameNode,
                          nextFocus: authController.fNameNode,
                          inputAction: TextInputAction.next,
                          autoFocus: authController.usernameController.text.isEmpty,
                        ),
                        const SizedBox(height: Dimensions.paddingSizeDefault),

                        TextFieldTitleWidget(title: 'first_name'.tr, isRequired: true),

                        TextFieldWidget(
                          hintText: 'enter_your_first_name'.tr,
                          capitalization: TextCapitalization.words,
                          inputType: TextInputType.name,
                          prefixIcon: Images.person,
                          controller: authController.fNameController,
                          focusNode: authController.fNameNode,
                          nextFocus: authController.lNameNode,
                          inputAction: TextInputAction.next,
                        ),
                        const SizedBox(height: Dimensions.paddingSizeDefault),

                        TextFieldTitleWidget(title: 'last_name'.tr),

                        TextFieldWidget(
                          hintText: 'enter_your_last_name'.tr,
                          capitalization: TextCapitalization.words,
                          inputType: TextInputType.name,
                          prefixIcon: Images.person,
                          controller: authController.lNameController,
                          focusNode: authController.lNameNode,
                          inputAction: TextInputAction.next,
                        ),

                        if (Get.find<SplashController>().config?.referralEarningStatus ?? false) ...[
                          const SizedBox(height: Dimensions.paddingSizeDefault),
                          TextFieldTitleWidget(title: 'referral_code'.tr),

                          TextFieldWidget(
                            hintText: 'enter_refer_code'.tr,
                            capitalization: TextCapitalization.words,
                            inputType: TextInputType.text,
                            prefixIcon: Images.referIcon,
                            controller: authController.referralCodeController,
                            focusNode: authController.referralNode,
                            inputAction: TextInputAction.done,
                          ),
                        ],

                        const SizedBox(height: Dimensions.paddingSizeDefault),
                        TextFieldTitleWidget(title: 'pin'.tr, isRequired: true),

                        VitoPinField(
                          controller: authController.passwordController,
                          focusNode: authController.passwordNode,
                        ),

                        const SizedBox(height: Dimensions.paddingSizeDefault),
                        TextFieldTitleWidget(title: 'confirm_password'.tr, isRequired: true),

                        VitoPinField(
                          controller: authController.confirmPasswordController,
                          focusNode: authController.confirmPasswordNode,
                          errorController: _confirmPinErrorController,
                        ),
                      ]),
                    ),
                    const SizedBox(height: Dimensions.paddingSizeLarge),
                  ])
              )),

              Container(
                decoration: BoxDecoration(
                    boxShadow: [BoxShadow(color: Theme.of(context).hintColor.withValues(alpha: 0.15), blurRadius: 10, offset: const Offset(0, -4))],
                    borderRadius: const BorderRadius.only(topRight: Radius.circular(Dimensions.paddingSizeLarge), topLeft: Radius.circular(Dimensions.paddingSizeLarge)),
                    color: Theme.of(context).cardColor
                ),
                padding: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeSmall, horizontal: Dimensions.paddingSizeExtraSmall)
                    .copyWith(bottom: Dimensions.paddingSizeExtraLarge),
                child: ButtonWidget(
                  margin: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                  radius: Dimensions.radiusExtraLarge,
                  buttonText: 'next'.tr,
                  onPressed: () {
                    final String username = authController.usernameController.text.trim();
                    final String fName = authController.fNameController.text;
                    final String password = authController.passwordController.text;
                    final String confirmPassword = authController.confirmPasswordController.text;

                    if (username.isEmpty) {
                      showCustomSnackBar('username_is_required'.tr);
                      FocusScope.of(context).requestFocus(authController.usernameNode);
                    } else if (username.length < 3) {
                      showCustomSnackBar('username_min_3_characters'.tr);
                      FocusScope.of(context).requestFocus(authController.usernameNode);
                    } else if (fName.isEmpty) {
                      showCustomSnackBar('first_name_is_required'.tr);
                      FocusScope.of(context).requestFocus(authController.fNameNode);
                    } else if (password.isEmpty) {
                      showCustomSnackBar('pin_is_required'.tr);
                    } else if (!RegExp(r'^\d{6}$').hasMatch(password)) {
                      showCustomSnackBar('pin_must_be_6_digits'.tr);
                    } else if (confirmPassword.isEmpty) {
                      showCustomSnackBar('confirm_password_is_required'.tr);
                    } else if (password != confirmPassword) {
                      showCustomSnackBar('password_is_mismatch'.tr);
                      _confirmPinErrorController.add(ErrorAnimationType.shake);
                    } else {
                      Get.to(() => const AdditionalSignUpScreen2());
                    }
                  },
                ),
              ),
            ]);
          })),
    );
  }
}
