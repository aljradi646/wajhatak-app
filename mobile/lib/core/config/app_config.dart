import 'package:flutter/foundation.dart';

class AppConfig {
  const AppConfig._();

  static const apiBaseUrl = String.fromEnvironment(
    'WAJHATAK_API_BASE_URL',
    defaultValue: 'https://marvelous-warmth-production-803a.up.railway.app/api/v1',
  );

  static const connectTimeout = Duration(seconds: 12);
  static const receiveTimeout = Duration(seconds: 20);

  static const _previewRequested = bool.fromEnvironment(
    'WAJHATAK_UI_PREVIEW',
    defaultValue: false,
  );

  static bool get isUiPreview => kDebugMode && _previewRequested;

  static const previewRole = String.fromEnvironment(
    'WAJHATAK_UI_PREVIEW_ROLE',
    defaultValue: 'client',
  );

  static const appName = 'وجهتك';
  static const appNameEn = 'Wajhatak';
  static const appTagline = 'وجهتك إلى العقار المناسب.';
}