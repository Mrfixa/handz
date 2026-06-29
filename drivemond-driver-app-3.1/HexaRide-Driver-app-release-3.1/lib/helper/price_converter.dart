import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/features/splash/controllers/splash_controller.dart';
import 'package:ride_sharing_user_app/util/parse_utils.dart';

class PriceConverter {

  static String convertPrice(BuildContext context, double price, {double? discount, String? discountType}) {
    bool inRight = Get.find<SplashController>().config!.currencySymbolPosition == 'right';
    String decimal = Get.find<SplashController>().config!.currencyDecimalPoint?? '1';
    // Server-supplied; toStringAsFixed needs 0..20, and a non-numeric value would
    // otherwise throw on every price render. Coerce safely and clamp.
    final int decimalDigits = toIntOr(decimal, 1).clamp(0, 20);
    String symbol = Get.find<SplashController>().config!.currencySymbol?? '\$';
    String finalResult;
    if(discount != null && discountType != null){
      if(discountType == 'amount') {
        price = price - discount;
      }else if(discountType == 'percent') {
        price = price - ((discount / 100) * price);
      }
    }
    if(inRight){
      finalResult = '${(price).toStringAsFixed(decimalDigits).replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (Match m) => '${m[1]},')} $symbol';
    }else{
      finalResult = '$symbol ''${(price).toStringAsFixed(decimalDigits).replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (Match m) => '${m[1]},')}';
    }
    return finalResult;
  }

  static double convertWithDiscount(BuildContext context, double price, double discount, String discountType) {
    if(discountType == 'amount') {
      price = price - discount;
    }else if(discountType == 'percent') {
      price = price - ((discount / 100) * price);
    }
    return price;
  }

  static double calculation(double amount, double discount, String type, int quantity) {
    double calculatedAmount = 0;
    if(type == 'amount') {
      calculatedAmount = discount * quantity;
    }else if(type == 'percent') {
      calculatedAmount = (discount / 100) * (amount * quantity);
    }
    return calculatedAmount;
  }

  static String percentageCalculation(BuildContext context, String price, String discount, String discountType) {
    return '$discount${discountType == 'percent' ? '%' : '\$'} OFF';
  }
}