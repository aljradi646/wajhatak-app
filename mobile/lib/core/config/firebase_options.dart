import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/foundation.dart';

/// Firebase app identifiers are passed at build time. Service-account private
/// keys are server-only and must never be included in the mobile app.
class WajhatakFirebaseOptions {
  const WajhatakFirebaseOptions._();

  static const projectId = String.fromEnvironment('WAJHATAK_FIREBASE_PROJECT_ID');
  static const senderId = String.fromEnvironment('WAJHATAK_FIREBASE_MESSAGING_SENDER_ID');
  static const apiKey = String.fromEnvironment('WAJHATAK_FIREBASE_API_KEY');
  static const androidAppId = String.fromEnvironment('WAJHATAK_FIREBASE_ANDROID_APP_ID');
  static const iosAppId = String.fromEnvironment('WAJHATAK_FIREBASE_IOS_APP_ID');
  static const iosBundleId = String.fromEnvironment('WAJHATAK_FIREBASE_IOS_BUNDLE_ID');
  static const storageBucket = String.fromEnvironment('WAJHATAK_FIREBASE_STORAGE_BUCKET');

  static FirebaseOptions? get currentPlatform {
    if (projectId.isEmpty || senderId.isEmpty || apiKey.isEmpty || kIsWeb) {
      return null;
    }

    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        if (androidAppId.isEmpty) return null;
        return FirebaseOptions(
          apiKey: apiKey,
          appId: androidAppId,
          messagingSenderId: senderId,
          projectId: projectId,
          storageBucket: storageBucket.isEmpty ? null : storageBucket,
        );
      case TargetPlatform.iOS:
        if (iosAppId.isEmpty || iosBundleId.isEmpty) return null;
        return FirebaseOptions(
          apiKey: apiKey,
          appId: iosAppId,
          messagingSenderId: senderId,
          projectId: projectId,
          storageBucket: storageBucket.isEmpty ? null : storageBucket,
          iosBundleId: iosBundleId,
        );
      default:
        return null;
    }
  }
}
