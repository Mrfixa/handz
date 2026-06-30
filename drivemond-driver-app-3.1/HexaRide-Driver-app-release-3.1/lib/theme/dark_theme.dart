import 'package:flutter/material.dart';

import 'custom_theme_colors.dart';

ThemeData darkTheme = ThemeData(
  fontFamily: 'SFProText',
  primaryColor: const Color(0xFF14B19E),
  brightness: Brightness.dark,
  cardColor: const Color(0xFF242424),
  hintColor: const Color(0xFF9F9F9F),
  scaffoldBackgroundColor: const Color(0xFF121212),
  primaryColorDark: const Color(0xFF007B6C),

    extensions: <ThemeExtension<CustomThemeColors>>[
      CustomThemeColors.dark()
    ],


  colorScheme: const ColorScheme.dark(
      primary: Color(0xFF14B19E),
      error: Color(0xFFFF6767),
      secondary: Color(0xFF1B2838),
      tertiary: Color(0xFF7CCD8B),
      tertiaryContainer: Color(0xFFC98B3E),
      secondaryContainer: Color(0xFFEE6464),
      onTertiary: Color(0xFFD9D9D9),
      onSecondary: Color(0xFF00FEE1),
      onSecondaryContainer: Color(0xFFA8C5C1),
      onTertiaryContainer: Color(0xFF425956),
      outline: Color(0xFF8CFFF1),
      onPrimaryContainer: Color(0xFF929494),
      primaryContainer: Color(0xFF008C7B),
      onSurface: Color(0xFFF1F1F1),
      onPrimary: Color(0xFFFFFFFF),
      surfaceContainer: Color(0xFF016ACD),
      secondaryFixedDim: Color(0xFF808080)

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
    // Dark-mode text must be light — the originals were near-black (invisible on the dark background).
    textTheme: const TextTheme(
      displayLarge: TextStyle(fontWeight: FontWeight.w300, color: Color(0xFFF1F1F1)),
      displayMedium: TextStyle(fontWeight: FontWeight.w300, color: Color(0xFFE0E0E0)),
      displaySmall: TextStyle(fontWeight: FontWeight.w300, color: Color(0xFFF1F1F1)),
      bodyLarge: TextStyle(fontWeight: FontWeight.w300, color: Color(0xFFFFFFFF)),
      bodyMedium: TextStyle(fontWeight: FontWeight.w300, color: Color(0xffffffff)),
      bodySmall: TextStyle(fontWeight: FontWeight.w300, color: Color(0xFFD5E1E0)),
    )
);
