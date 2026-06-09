import 'package:dotted_border/dotted_border.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

class DottedBorderBoxWidget extends StatelessWidget {
  final double? height;
  final double? width;
  final Function() onTap;
  const DottedBorderBoxWidget({super.key, required this.onTap, this.height=100, this.width=100});

  @override
  Widget build(BuildContext context) {
    return DottedBorder(
      options: RoundedRectDottedBorderOptions(
        dashPattern: const [8, 4],
        strokeWidth: 1,
        color: Theme.of(context).hintColor,
        radius: Radius.circular(Dimensions.radiusDefault),
      ),
      child: InkWell(onTap: onTap,
        child: SizedBox(height: height, width: width,
          child: Center(child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
            Icon(Icons.cloud_upload_rounded, color: Theme.of(context).textTheme.bodyLarge!.color!.withValues(alpha: 0.6), size: 30),
                SizedBox(height: Dimensions.paddingSizeExtraSmall),
                Text("upload_file".tr, style: textMedium.copyWith(fontSize: Dimensions.fontSizeSmall,
                    color: Theme.of(context).textTheme.bodyLarge!.color!.withValues(alpha: 0.6))),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
