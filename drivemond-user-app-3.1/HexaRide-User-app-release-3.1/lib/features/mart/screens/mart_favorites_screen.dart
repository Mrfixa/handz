import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/common_widgets/app_bar_widget.dart';
import 'package:ride_sharing_user_app/features/mart/controllers/mart_controller.dart';
import 'package:ride_sharing_user_app/helper/price_converter.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/images.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

/// GoMart favorites / wishlist — the customer's saved products.
class MartFavoritesScreen extends StatefulWidget {
  const MartFavoritesScreen({super.key});

  @override
  State<MartFavoritesScreen> createState() => _MartFavoritesScreenState();
}

class _MartFavoritesScreenState extends State<MartFavoritesScreen> {
  @override
  void initState() {
    super.initState();
    Get.find<MartController>().getFavorites();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBarWidget(title: 'favorites'.tr, regularAppbar: true),
      body: GetBuilder<MartController>(builder: (martController) {
        final items = martController.favorites;
        if (items.isEmpty) {
          return Center(
            child: Text('no_favorites_yet'.tr,
                style: textRegular.copyWith(color: Theme.of(context).disabledColor)),
          );
        }
        return RefreshIndicator(
          onRefresh: () => martController.getFavorites(),
          child: ListView.separated(
            padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
            itemCount: items.length,
            separatorBuilder: (_, __) => const SizedBox(height: Dimensions.paddingSizeSmall),
            itemBuilder: (context, index) {
              final p = items[index];
              return Container(
                padding: const EdgeInsets.all(Dimensions.paddingSizeSmall),
                decoration: BoxDecoration(
                  color: Theme.of(context).cardColor,
                  borderRadius: BorderRadius.circular(Dimensions.radiusDefault),
                ),
                child: Row(children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(Dimensions.radiusSmall),
                    child: CachedNetworkImage(
                      width: 56, height: 56, fit: BoxFit.cover,
                      imageUrl: p.image ?? '',
                      errorWidget: (_, __, ___) => Image.asset(Images.logo, width: 56, height: 56),
                    ),
                  ),
                  const SizedBox(width: Dimensions.paddingSizeSmall),
                  Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(p.name ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, style: textBold),
                      Row(children: [
                        Text(PriceConverter.convertPrice(p.effectivePrice),
                            style: textBold.copyWith(color: Theme.of(context).primaryColor)),
                        if (p.onSale) ...[
                          const SizedBox(width: 6),
                          Text(PriceConverter.convertPrice(p.price),
                              style: textRegular.copyWith(
                                color: Theme.of(context).disabledColor,
                                decoration: TextDecoration.lineThrough,
                                fontSize: Dimensions.fontSizeSmall,
                              )),
                        ],
                        if (p.unit != null && p.unit!.isNotEmpty) ...[
                          const SizedBox(width: 6),
                          Text(p.unit!, style: textRegular.copyWith(
                              color: Theme.of(context).disabledColor, fontSize: Dimensions.fontSizeSmall)),
                        ],
                      ]),
                    ]),
                  ),
                  IconButton(
                    icon: Icon(Icons.favorite, color: Theme.of(context).colorScheme.error),
                    onPressed: () async {
                      await martController.toggleFavorite(p.id ?? '');
                      martController.getFavorites();
                    },
                  ),
                ]),
              );
            },
          ),
        );
      }),
    );
  }
}
