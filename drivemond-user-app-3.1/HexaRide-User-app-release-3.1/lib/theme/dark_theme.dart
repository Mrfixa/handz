import 'package:flutter/material.dart';
import 'package:ride_sharing_user_app/theme/custom_theme_color.dart';
import 'package:ride_sharing_user_app/util/app_constants.dart';

ThemeData darkTheme = ThemeData(
  fontFamily: AppConstants.fontFamily,
  primaryColor: const Color(0xFF14B19E),
  primaryColorDark: const Color(0xFF007B6C),
  disabledColor: const Color(0xFFBABFC4),
  scaffoldBackgroundColor: const Color(0xFF121212),
  canvasColor: const Color(0xFF1C1F1F),
  shadowColor: Colors.white.withValues(alpha:0.03),
  brightness: Brightness.dark,
  hintColor: const Color(0xFF9F9F9F),
  cardColor: const Color(0xFF242424),
  textTheme:  const TextTheme(
    bodyMedium: TextStyle(color: Colors.white),
    bodySmall: TextStyle(color: Color(0xffd5e1e0)),
    bodyLarge: TextStyle(color: Color(0xffffffff)),
    // Was near-black (invisible on the dark background).
    titleMedium: TextStyle(color: Color(0xffF1F1F1)),
  ),
  colorScheme: const ColorScheme.dark(
    primary: Color(0xFF14B19E),
    error: Color(0xFFFF6767),
    surface: Color(0xFFF3F3F3),
    secondary: Color(0xFF1B2838),
    tertiary: Color(0xFF7CCD8B),
    tertiaryContainer: Color(0xFFC98B3E),
    secondaryContainer: Color(0xFFEE6464),
    onTertiary: Color(0xFFD9D9D9),
    onSecondary: Color(0xFF00FEE1),
    onSecondaryContainer: Color(0xFFA8C5C1),
    onTertiaryContainer: Color(0xFF425956),
    outline: Color(0xFF8CFFF1),
    onPrimaryContainer: Color(0xFFDEFFFB),
    errorContainer: Color(0xFFF6F6F6),
    primaryContainer: Color(0xFF008C7B),
    onSurface: Color(0xFF1D2D2B),
    onPrimary: Color(0xFFFFFFFF),
    inverseSurface: Color(0xFF0148AF),
    surfaceContainer: Color(0xFF0094FF),
    secondaryFixedDim: Color(0xff808080),

  ),
  textButtonTheme: TextButtonThemeData(style: TextButton.styleFrom(foregroundColor: const Color(0xFF14B19E))),

  dialogTheme: DialogThemeData(
    backgroundColor: const Color(0xFF242424),
    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
  ),
  cardTheme: CardThemeData(
    elevation: 1,
    color: const Color(0xFF242424),
    surfaceTintColor: Colors.transparent,
    shadowColor: Colors.black.withValues(alpha: 0.2),
    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
    clipBehavior: Clip.antiAlias,
  ),
  bottomSheetTheme: const BottomSheetThemeData(
    backgroundColor: Color(0xFF1C1F1F),
    shape: RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
    clipBehavior: Clip.antiAlias,
  ),
  snackBarTheme: SnackBarThemeData(
    behavior: SnackBarBehavior.floating,
    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
  ),

  extensions: <ThemeExtension<CustomThemeColors>>[
    CustomThemeColors.dark(),
  ],
);
