import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:shimmer/shimmer.dart';

class LastTripShimmerWidget extends StatelessWidget {
  const LastTripShimmerWidget({super.key});

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Padding(
      padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
      child: Shimmer.fromColors(
        baseColor: isDark ? const Color(0xFF2D2D2D) : Colors.grey[300]!,
        highlightColor: isDark ? const Color(0xFF404040) : Colors.grey[100]!,
        child: Container(width: Get.width, height: 300,
          decoration: BoxDecoration(
            color: Theme.of(context).primaryColor.withValues(alpha: 0.07),
            borderRadius: const BorderRadius.all(Radius.circular(Dimensions.radiusLarge)),
            border: Border.all(color: Theme.of(context).hintColor),
          ),
          padding: const EdgeInsets.symmetric(
            horizontal: Dimensions.paddingSizeSmall,
            vertical: Dimensions.paddingSizeSmall,
          ),
          child: Column(
            children: [
              Row(children: [
                Column(
                  children: [
                    Container(
                      decoration: BoxDecoration(
                        color: Theme.of(context).primaryColor.withValues(alpha: 0.07),
                        borderRadius: const BorderRadius.all(Radius.circular(Dimensions.radiusLarge)),
                      ),
                      padding: const EdgeInsets.symmetric(
                        horizontal: Dimensions.paddingSizeSmall,
                        vertical: Dimensions.paddingSizeSmall,
                      ),
                      child: Container(width: 50, height: 10,
                        decoration: BoxDecoration(
                          color: isDark ? const Color(0xFF303030) : Colors.white,
                          borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
                        ),
                      ),
                    ),
                    const SizedBox(height: Dimensions.paddingSizeSmall),
                    Container(
                      decoration: BoxDecoration(
                        color: Theme.of(context).primaryColor.withValues(alpha: 0.07),
                        borderRadius: const BorderRadius.all(Radius.circular(Dimensions.radiusLarge)),
                      ),
                      padding: const EdgeInsets.symmetric(
                        horizontal: Dimensions.paddingSizeSmall,
                        vertical: Dimensions.paddingSizeSmall,
                      ),
                      child: Container(width: 50, height: 10,
                        decoration: BoxDecoration(
                          color: isDark ? const Color(0xFF303030) : Colors.white,
                          borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
                        ),
                      ),
                    ),
                  ],
                ),
                const Spacer(),
                Container(
                  decoration: BoxDecoration(
                    color: Theme.of(context).primaryColor.withValues(alpha: 0.07),
                    borderRadius: const BorderRadius.all(Radius.circular(Dimensions.radiusLarge)),
                  ),
                  padding: const EdgeInsets.symmetric(
                    horizontal: Dimensions.paddingSizeSmall,
                    vertical: Dimensions.paddingSizeSmall,
                  ),
                  child: Container(width: 50, height: 10,
                    decoration: BoxDecoration(
                      color: isDark ? const Color(0xFF303030) : Colors.white,
                      borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
                    ),
                  ),
                ),
              ],),
              const SizedBox(height: Dimensions.paddingSizeSmall,),
              Row(children: [
                Container(
                  decoration: BoxDecoration(
                    color: Theme.of(context).primaryColor.withValues(alpha: 0.07),
                    borderRadius: const BorderRadius.all(Radius.circular(Dimensions.radiusLarge)),
                  ),
                  padding: const EdgeInsets.symmetric(
                    horizontal: Dimensions.paddingSizeSmall,
                    vertical: Dimensions.paddingSizeSmall,
                  ),
                  child: Container(width: 50, height: 10,
                    decoration: BoxDecoration(
                      color: isDark ? const Color(0xFF303030) : Colors.white,
                      borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
                    ),
                  ),
                ),
                const Spacer(),
                Container(
                  decoration: BoxDecoration(
                    color: Theme.of(context).primaryColor.withValues(alpha: 0.07),
                    borderRadius: const BorderRadius.all(Radius.circular(Dimensions.radiusLarge)),
                  ),
                  padding: const EdgeInsets.symmetric(
                    horizontal: Dimensions.paddingSizeSmall,
                    vertical: Dimensions.paddingSizeSmall,
                  ),
                  child: Container(width: 100, height: 100,
                    decoration: BoxDecoration(
                      color: isDark ? const Color(0xFF303030) : Colors.white,
                      borderRadius: BorderRadius.circular(200),
                    ),
                  ),
                ),
                const SizedBox(height: Dimensions.paddingSizeSmall),
                const Spacer(),
                Container(
                  decoration: BoxDecoration(
                    color: Theme.of(context).primaryColor.withValues(alpha: 0.07),
                    borderRadius: const BorderRadius.all(Radius.circular(Dimensions.radiusLarge)),
                  ),
                  padding: const EdgeInsets.symmetric(
                    horizontal: Dimensions.paddingSizeSmall,
                    vertical: Dimensions.paddingSizeSmall,
                  ),
                  child: Container(width: 50, height: 10,
                    decoration: BoxDecoration(
                      color: isDark ? const Color(0xFF303030) : Colors.white,
                      borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
                    ),
                  ),
                ),
              ],),
              const SizedBox(height: Dimensions.paddingSizeSmall,),
              Row(children: [
                Container(
                  decoration: BoxDecoration(
                    color: Theme.of(context).primaryColor.withValues(alpha: 0.07),
                    borderRadius: const BorderRadius.all(Radius.circular(Dimensions.radiusLarge)),
                  ),
                  padding: const EdgeInsets.symmetric(
                    horizontal: Dimensions.paddingSizeSmall,
                    vertical: Dimensions.paddingSizeSmall,
                  ),
                  child: Container(width: 50, height: 10,
                    decoration: BoxDecoration(
                      color: isDark ? const Color(0xFF303030) : Colors.white,
                      borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
                    ),
                  ),
                ),
                const Spacer(),
                Container(
                  decoration: BoxDecoration(
                    color: Theme.of(context).primaryColor.withValues(alpha: 0.07),
                    borderRadius: const BorderRadius.all(Radius.circular(Dimensions.radiusLarge)),
                  ),
                  padding: const EdgeInsets.symmetric(
                    horizontal: Dimensions.paddingSizeSmall,
                    vertical: Dimensions.paddingSizeSmall,
                  ),
                  child: Container(width: 50, height: 10,
                    decoration: BoxDecoration(
                      color: isDark ? const Color(0xFF303030) : Colors.white,
                      borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
                    ),
                  ),
                ),
              ],),
              const SizedBox(height: Dimensions.paddingSizeExtraSmall,),
              Row(children: [
                Container(
                  decoration: BoxDecoration(
                    color: Theme.of(context).primaryColor.withValues(alpha: 0.07),
                    borderRadius: const BorderRadius.all(Radius.circular(Dimensions.radiusLarge)),
                  ),
                  padding: const EdgeInsets.symmetric(
                    horizontal: Dimensions.paddingSizeSmall,
                    vertical: Dimensions.paddingSizeSmall,
                  ),
                  child: Container(width: 50, height: 10,
                    decoration: BoxDecoration(
                      color: isDark ? const Color(0xFF303030) : Colors.white,
                      borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
                    ),
                  ),
                ),
                const Spacer(),
                Container(
                  decoration: BoxDecoration(
                    color: Theme.of(context).primaryColor.withValues(alpha: 0.07),
                    borderRadius: const BorderRadius.all(Radius.circular(Dimensions.radiusLarge)),
                  ),
                  padding: const EdgeInsets.symmetric(
                    horizontal: Dimensions.paddingSizeSmall,
                    vertical: Dimensions.paddingSizeSmall,
                  ),
                  child: Container(width: 50, height: 10,
                    decoration: BoxDecoration(
                      color: isDark ? const Color(0xFF303030) : Colors.white,
                      borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
                    ),
                  ),
                ),
              ],),
            ],
          ),
        ),
      ),
    );
  }
}
