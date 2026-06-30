import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

class ButtonWidget extends StatelessWidget {
  final Function()? onPressed;
  final String buttonText;
  final bool transparent;
  final EdgeInsets margin;
  final double height;
  final double width;
  final double? fontSize;
  final double radius;
  final IconData? icon;
  final bool showBorder;
  final double borderWidth;
  final Color? borderColor;
  final Color? textColor;
  final Color? backgroundColor;
  final bool boldText;
  final String? semanticLabel;
  /// When true, shows a spinner and blocks taps while keeping the active background.
  final bool isLoading;
  /// Light haptic on tap for a premium feel; opt out for rapid-fire buttons.
  final bool enableHaptic;
  const ButtonWidget({super.key, this.onPressed,
    required this.buttonText,
    this.transparent = false,
    this.margin = EdgeInsets.zero,
    this.width = Dimensions.webMaxWidth, this.height = 48,
    this.fontSize,
    this.radius = 10, this.icon,
    this.showBorder = false,
    this.borderWidth=1,
    this.borderColor,
    this.textColor,
    this.backgroundColor,
    this.boldText = true,
    this.semanticLabel,
    this.isLoading = false,
    this.enableHaptic = true});

  @override
  Widget build(BuildContext context) {
    final ButtonStyle flatButtonStyle = TextButton.styleFrom(
      backgroundColor:
      backgroundColor ?? (onPressed == null ? Theme.of(context).disabledColor : transparent ? Colors.transparent : Theme.of(context).primaryColor),
      minimumSize: Size(width, height),
      padding: EdgeInsets.zero,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(radius),
        side: showBorder ?
        BorderSide(color: borderColor ?? Theme.of(context).primaryColor,width: borderWidth) :
        const BorderSide(color: Colors.transparent),
      ),
    );


    return Semantics(
      button: true,
      label: semanticLabel ?? buttonText,
      child: Center(child: SizedBox(
      width: width,
      child: Padding(padding: margin,
        child: TextButton(
          onPressed: (onPressed == null || isLoading) ? null : () {
            if (enableHaptic) HapticFeedback.selectionClick();
            onPressed!();
          },
          style: flatButtonStyle,
          child: isLoading
            ? SizedBox(
                height: 22, width: 22,
                child: CircularProgressIndicator(
                  strokeWidth: 2.5,
                  valueColor: AlwaysStoppedAnimation<Color>(
                    textColor ?? (transparent ? Theme.of(context).primaryColor : Colors.white),
                  ),
                ),
              )
            : Row(mainAxisAlignment: MainAxisAlignment.center, children: [
            icon != null ?
            Padding(
              padding: const EdgeInsets.only(right: Dimensions.paddingSizeExtraSmall),
              child: Icon(icon, color: transparent ? Theme.of(context).primaryColor : Colors.white),
            ) :
            const SizedBox(),

            Flexible(
              child: Text(
                buttonText, textAlign: TextAlign.center,
                style: boldText ?
                textBold.copyWith(
                  color: textColor ?? (transparent ? Theme.of(context).primaryColor : Colors.white),
                  fontSize: fontSize ?? Dimensions.fontSizeDefault,
                  overflow: TextOverflow.ellipsis,
                ) :
                textRegular.copyWith(
                  color: textColor ?? (transparent ? Theme.of(context).primaryColor : Colors.white),
                  fontSize: fontSize ?? Dimensions.fontSizeLarge,
                ),
              ),
            ),
          ]),
        ),
      ),
    )));
  }
}