import 'package:flutter/material.dart';

import 'custom_theme_colors.dart';

ThemeData darkTheme = ThemeData(
  fontFamily: 'SFProText',
  primaryColor: const Color(0xFFD4A000),
  brightness: Brightness.dark,
  cardColor: const Color(0xFF242424),
  hintColor: const Color(0xFF9F9F9F),
  scaffoldBackgroundColor: const Color(0xFF1B2838),
  canvasColor: const Color(0xFF1B2838),
  dividerColor: Colors.white24,
  primaryColorDark: const Color(0xFFB38600),

  extensions: <ThemeExtension<CustomThemeColors>>[
    CustomThemeColors.dark()
  ],

  colorScheme: const ColorScheme.dark(
    primary: Color(0xFFF5B800),
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
    primaryContainer: Color(0xFFFFA800),
    onSurface: Color(0xFFFFE6AD),
    onPrimary: Color(0xFFFFFFFF),
    surfaceContainer: Color(0xFF016ACD),
    secondaryFixedDim: Color(0xFF808080)
  ),

  textButtonTheme: TextButtonThemeData(style: TextButton.styleFrom(foregroundColor: const Color(0xFFD4A000))),

  textTheme: const TextTheme(
    displayLarge: TextStyle(fontWeight: FontWeight.w300, color: Colors.white),
    displayMedium: TextStyle(fontWeight: FontWeight.w300, color: Colors.white),
    displaySmall: TextStyle(fontWeight: FontWeight.w300, color: Colors.white),
    bodyLarge: TextStyle(fontWeight: FontWeight.w300, color: Colors.white),
    bodyMedium: TextStyle(fontWeight: FontWeight.w300, color: Colors.white70),
    bodySmall: TextStyle(fontWeight: FontWeight.w300, color: Colors.white70),
  ),
);
