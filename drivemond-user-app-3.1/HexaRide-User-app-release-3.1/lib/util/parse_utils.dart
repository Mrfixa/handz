/// Safe numeric coercion for server-supplied values that may arrive as `null`,
/// a `String`, or a `num`. Using these instead of `int.parse` / `double.parse`
/// on API fields prevents `FormatException` crashes when the backend returns an
/// unexpected shape (null, empty, or non-numeric). Each returns a caller-supplied
/// fallback instead of throwing.

double toDoubleOr(dynamic value, [double fallback = 0]) {
  if (value is num) return value.toDouble();
  if (value == null) return fallback;
  return double.tryParse(value.toString()) ?? fallback;
}

int toIntOr(dynamic value, [int fallback = 0]) {
  if (value is num) return value.toInt();
  if (value == null) return fallback;
  return int.tryParse(value.toString()) ?? double.tryParse(value.toString())?.toInt() ?? fallback;
}

int? toIntOrNull(dynamic value) {
  if (value is num) return value.toInt();
  if (value == null) return null;
  return int.tryParse(value.toString()) ?? double.tryParse(value.toString())?.toInt();
}
