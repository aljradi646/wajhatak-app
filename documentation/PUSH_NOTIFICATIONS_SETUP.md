# Firebase push notifications for Wajhatak

Laravel database notifications remain the durable source of truth. Firebase Cloud Messaging (FCM) adds OS tray delivery when the app is backgrounded or terminated. If Firebase has not been configured, in-app notifications continue to work; remote delivery to a closed app is not available until the steps below are completed.

## Firebase project setup

1. Create/select a Firebase project and enable Firebase Cloud Messaging.
2. Register the Android app using the application ID from \`mobile/android/app/build.gradle.kts\` (\`com.wajhatak.app\` in the current app).
3. For iOS, register the actual bundle identifier and configure APNs in the Firebase console with the team's APNs key.
4. Create a Firebase service account with permission to send FCM HTTP v1 messages. Keep the service-account JSON outside the repository.

## Laravel/Railway backend secrets

Set these on the Laravel application service:

- \`FIREBASE_PROJECT_ID\`: the Firebase project ID.
- \`FIREBASE_CREDENTIALS\`: absolute path to a mounted service-account JSON file (preferred for deployment).
- \`FIREBASE_SERVICE_ACCOUNT_JSON\`: alternative for hosting platforms that securely store a full JSON secret as an environment variable.

Do not add service-account JSON, private keys, or production credentials to Git. After changing secrets, restart/redeploy the Laravel service so cached configuration is rebuilt.

The backend obtains/caches OAuth access tokens and sends notifications to device tokens registered against the signed-in user. A push failure is logged without removing the durable Laravel database notification or failing the admin's reply operation. Tokens marked unregistered by FCM are removed from the device table.

## Flutter build-time defines

Build the mobile app with the client configuration for the same Firebase project. These are app configuration values, not service-account private keys:

- \`WAJHATAK_FIREBASE_PROJECT_ID\`
- \`WAJHATAK_FIREBASE_MESSAGING_SENDER_ID\`
- \`WAJHATAK_FIREBASE_API_KEY\`
- \`WAJHATAK_FIREBASE_ANDROID_APP_ID\`
- \`WAJHATAK_FIREBASE_IOS_APP_ID\` (for iOS builds)
- \`WAJHATAK_FIREBASE_IOS_BUNDLE_ID\` (for iOS builds)
- \`WAJHATAK_FIREBASE_STORAGE_BUCKET\` (optional)

Example for Android, replacing the values with those from the Firebase console:

\`\`\`text
--dart-define=WAJHATAK_FIREBASE_PROJECT_ID=your-project-id
--dart-define=WAJHATAK_FIREBASE_MESSAGING_SENDER_ID=your-sender-id
--dart-define=WAJHATAK_FIREBASE_API_KEY=your-firebase-api-key
--dart-define=WAJHATAK_FIREBASE_ANDROID_APP_ID=1:sender-id:android:app-id
\`\`\`

When these defines are omitted, the FCM client remains inactive and does not block app launch. The app requests notification permission, registers the FCM token for the current account, and removes that device registration at sign-out. Foreground pushes are displayed as local notifications; a tap carries the durable notification ID and opens the matching in-app content.

## End-to-end acceptance test

After Firebase and APNs/Android setup:
1. Install the freshly built app on a real device and allow notification permission.
2. Sign in and verify the device is recorded under the correct user in \`user_devices\`.
3. Submit a support ticket, reply to it from the web admin dashboard, and check the Laravel \`notifications\` row.
4. Put the app in the background and confirm a push appears in the status bar.
5. Tap it and confirm the ticket thread opens inside Wajhatak.
6. Sign out and confirm the device registration is removed from the user's account.

The repository CI can verify compilation/tests, but cannot verify provider delivery without real Firebase/APNs credentials and a real device.
