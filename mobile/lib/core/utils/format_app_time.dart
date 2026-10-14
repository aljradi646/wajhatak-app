import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

/// Formats user-facing times using the app preference or the device setting.
String formatAppTime(
  BuildContext context,
  DateTime value, {
  String preference = 'system',
}) {
  final use24Hour =
      preference == '24' ||
      (preference == 'system' && MediaQuery.alwaysUse24HourFormatOf(context));
  final local = value.toLocal();
  return use24Hour
      ? DateFormat('HH:mm', 'ar').format(local)
      : DateFormat('h:mm a', 'ar').format(local);
}
