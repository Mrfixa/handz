import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/common_widgets/app_bar_widget.dart';
import 'package:ride_sharing_user_app/common_widgets/body_widget.dart';
import 'package:ride_sharing_user_app/common_widgets/error_retry_widget.dart';
import 'package:ride_sharing_user_app/common_widgets/no_data_widget.dart';
import 'package:ride_sharing_user_app/common_widgets/skeleton_widget.dart';
import 'package:ride_sharing_user_app/features/ride/controllers/ride_controller.dart';
import 'package:ride_sharing_user_app/features/ride/widgets/ride_item_widget.dart';

class RideListViewScreen extends StatefulWidget {
  const RideListViewScreen({super.key});

  @override
  State<RideListViewScreen> createState() => _RideListViewScreenState();
}

class _RideListViewScreenState extends State<RideListViewScreen> {
  bool _loading = false;
  bool _error = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (!mounted) return;
    setState(() {
      _loading = true;
      _error = false;
    });
    try {
      await Get.find<RideController>().getRunningRideList();
    } catch (_) {
      if (mounted) setState(() => _error = true);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      top: false,
      child: Scaffold(
        body: BodyWidget(
          appBar: AppBarWidget(title: 'ride_list_view'.tr),
          body: GetBuilder<RideController>(builder: (rideController) {
            if (_loading) return const SkeletonListView();
            if (_error) return ErrorRetryWidget(onRetry: _load);
            final data = rideController.runningRideList?.data;
            if (data == null || data.isEmpty) {
              return const NoDataWidget(title: 'no_trip_found');
            }
            return RefreshIndicator(
              onRefresh: _load,
              child: ListView.builder(
                padding: EdgeInsets.zero,
                shrinkWrap: true,
                itemCount: data.length,
                itemBuilder: (context, index) =>
                    RideItemWidget(tripDetails: data[index], index: index),
              ),
            );
          }),
        ),
      ),
    );
  }
}
