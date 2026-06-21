import 'package:flutter/material.dart';
import 'package:shimmer/shimmer.dart';
import 'package:ride_sharing_user_app/util/app_colors.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';

/// Shimmering placeholder shown while content loads. Light/dark aware, built on
/// the shared shimmer tokens so every skeleton looks identical across the app.
class SkeletonWidget extends StatelessWidget {
  final double width;
  final double height;
  final double radius;
  final EdgeInsetsGeometry? margin;
  const SkeletonWidget({super.key, this.width = double.infinity, this.height = 16, this.radius = 8, this.margin});

  @override
  Widget build(BuildContext context) {
    final bool isDark = Theme.of(context).brightness == Brightness.dark;
    final Color base = isDark ? AppColors.shimmerBaseDark : AppColors.shimmerBaseLight;
    return Shimmer.fromColors(
      baseColor: base,
      highlightColor: isDark ? AppColors.shimmerHighlightDark : AppColors.shimmerHighlightLight,
      child: Container(
        width: width,
        height: height,
        margin: margin,
        decoration: BoxDecoration(color: base, borderRadius: BorderRadius.circular(radius)),
      ),
    );
  }
}

/// A column of card-row skeletons for list loading states. Shrink-wrapped so it
/// can sit inside other scrollables.
class SkeletonListView extends StatelessWidget {
  final int itemCount;
  final double itemHeight;
  final EdgeInsetsGeometry padding;
  const SkeletonListView({
    super.key,
    this.itemCount = 6,
    this.itemHeight = 64,
    this.padding = const EdgeInsets.all(Dimensions.paddingSizeDefault),
  });

  @override
  Widget build(BuildContext context) {
    final bool isDark = Theme.of(context).brightness == Brightness.dark;
    final Color base = isDark ? AppColors.shimmerBaseDark : AppColors.shimmerBaseLight;
    return Shimmer.fromColors(
      baseColor: base,
      highlightColor: isDark ? AppColors.shimmerHighlightDark : AppColors.shimmerHighlightLight,
      child: ListView.separated(
        padding: padding,
        physics: const NeverScrollableScrollPhysics(),
        shrinkWrap: true,
        itemCount: itemCount,
        separatorBuilder: (_, __) => const SizedBox(height: Dimensions.paddingSizeSmall),
        itemBuilder: (_, __) => _SkeletonRow(height: itemHeight, base: base),
      ),
    );
  }
}

class _SkeletonRow extends StatelessWidget {
  final double height;
  final Color base;
  const _SkeletonRow({required this.height, required this.base});

  @override
  Widget build(BuildContext context) {
    Widget bar(double w, double h) =>
        Container(width: w, height: h, decoration: BoxDecoration(color: base, borderRadius: BorderRadius.circular(6)));
    return SizedBox(
      height: height,
      child: Row(children: [
        Container(width: 48, height: 48, decoration: BoxDecoration(color: base, borderRadius: BorderRadius.circular(12))),
        const SizedBox(width: Dimensions.paddingSizeDefault),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisAlignment: MainAxisAlignment.center, children: [
            bar(double.infinity, 12),
            const SizedBox(height: 8),
            bar(120, 12),
          ]),
        ),
      ]),
    );
  }
}
