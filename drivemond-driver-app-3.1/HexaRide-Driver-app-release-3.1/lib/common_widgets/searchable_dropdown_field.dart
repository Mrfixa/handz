import 'package:flutter/material.dart';
import 'package:flutter_typeahead/flutter_typeahead.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

/// A searchable dropdown built on [TypeAheadField]: the user types to filter the
/// list to entries whose label contains the typed text, and picks one from the
/// suggestion overlay. Used for the vehicle brand / model / category selectors so
/// long lists become type-to-search instead of scroll-only. Tapping the field
/// with an empty query shows the full list, matching the old dropdown behaviour.
class SearchableDropdownField<T> extends StatelessWidget {
  final List<T> items;
  final TextEditingController controller;
  final String hintText;
  final String Function(T) itemLabel;
  final void Function(T) onSelected;

  const SearchableDropdownField({
    super.key,
    required this.items,
    required this.controller,
    required this.hintText,
    required this.itemLabel,
    required this.onSelected,
  });

  @override
  Widget build(BuildContext context) {
    return TypeAheadField<T>(
      controller: controller,
      suggestionsCallback: (pattern) {
        final query = pattern.trim().toLowerCase();
        if (query.isEmpty) return items;
        return items.where((item) => itemLabel(item).toLowerCase().contains(query)).toList();
      },
      builder: (context, fieldController, focusNode) {
        return Container(
          width: Get.width,
          padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
          decoration: BoxDecoration(
            color: Theme.of(context).cardColor,
            border: Border.all(width: .5, color: Theme.of(context).hintColor.withValues(alpha: .7)),
            borderRadius: BorderRadius.circular(Dimensions.paddingSizeOverLarge),
          ),
          child: TextField(
            controller: fieldController,
            focusNode: focusNode,
            cursorColor: Theme.of(context).primaryColor,
            style: textRegular.copyWith(color: Theme.of(context).textTheme.bodyMedium!.color),
            decoration: InputDecoration(
              border: InputBorder.none,
              hintText: hintText,
              hintStyle: textRegular.copyWith(color: Theme.of(context).hintColor),
              suffixIcon: Icon(Icons.keyboard_arrow_down, color: Theme.of(context).hintColor),
            ),
          ),
        );
      },
      itemBuilder: (context, item) {
        return Padding(
          padding: const EdgeInsets.all(Dimensions.paddingSizeSmall),
          child: Text(
            itemLabel(item).tr,
            style: textRegular.copyWith(color: Theme.of(context).textTheme.bodyMedium!.color),
          ),
        );
      },
      onSelected: (item) {
        controller.text = itemLabel(item).tr;
        onSelected(item);
      },
      emptyBuilder: (context) => Padding(
        padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
        child: Text(
          'no_match_found'.tr,
          style: textRegular.copyWith(color: Theme.of(context).hintColor),
        ),
      ),
    );
  }
}
