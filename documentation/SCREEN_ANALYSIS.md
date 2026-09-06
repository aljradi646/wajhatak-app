# Flutter Screen Analysis — Deep Dive

تحليل تفصيلي تفصيل لكل شاشة مع ترتيب العناصر على الشاشة، الـ Widgets المستخدمة، والعلاقات بينها. معد بناءً على قراءة الملفات الفعلية.

---

# 1. AuthScreen (auth_screen.dart — 581 سطر)

## الهدف
تسجيل الدخول وإنشاء حساب جديد. يعرض التبويبان: Login | Register.

## الترتيب على الشاشة

```
┌────────────────────────────────────┐
│ WajhatakBrandMark (usentral, animated)│
│  "وجّهتك — وجهتك إلى العقار المناسب"  │
├────────────────────────────────────┤
│ TabBar: [تسجيل الدخول] [التسجيل]   │
├────────────────────────────────────┤
│ (Login tab)                        │
│   TextFormField: البريد الإلكتروني  │
│   TextFormField: كلمة المرور       │
│   TextButton: نسيت كلمة المرور?    │
│   TextButton: تسجيل الدخول         │
├────────────────────────────────────┤
│ (Register tab)                     │
│   TextFormField: الاسم              │
│   TextFormField: البريد الإلكتروني  │
│   TextField: الهاتف (اختياري)      │
│   TextFormField: كلمة المرور        │
│   TextFormField: تأكيد كلمة المرور  │
│   SegmentedButton: نوع الحساب      │
│     [عميل] [وكيل عقاري]            │
│   (إذا وكيل): TextField: رقم الترخيص │
│   (إذا وكيل): TextField: وصف الوكيل │
│   TextButton: إنشاء حساب            │
└────────────────────────────────────┘
```

## Widgets الرئيسية
- `AuthScreen extends ConsumerStatefulWidget`
- `TabController` (2 tabs)
- `Form` + `TextFormField` (validators)
- `GlobalKey<FormState>`
- `SegmentedButton<AccountType>` — نوع الحساب
- `WajhatakBrandMark` — الشعار المتحرك
- `LuxAsyncView` / `AsyncValue.when` — حالة التحميل

## الـ State المحلي
```dart
final TextEditingController _nameController;
final TextEditingController _emailController;
final TextEditingController _phoneController;
final TextEditingController _passwordController;
final TextEditingController _confirmPasswordController;
final TextEditingController _licenseController;
final TextEditingController _bioController;
final GlobalKey<FormState> _formKey;
```

## الأحداث
- `onSubmit()` → التحقق من النموذج → `SessionController.login()` / `register()`
- `onAccountTypeChanged()` → إظهار/إخفاء حقول الوكيل

## API
- `ref.read(sessionProvider.notifier).login(email, password, deviceName)`
- `ref.read(sessionProvider.notifier).register(name, email, password, accountType, phone)`

---

# 2. HomeScreen (home_screen.dart — 383 سطر)

## الهدف
الصفحة الرئيسية — عرض العقارات المميزة والجديدة مع بحث سريع.

## الترتيب على الشاشة

```
┌────────────────────────────────────┐
│ WajhatakHeader                     │
│  (hamburger + logo + notification) │
├────────────────────────────────────┤
│ Hero Card (emerald gradient)       │
│  "مرحباً، [name]!"                 │
│  "اكتشف منزلك المثالي مع وجهتك"    │
│  ┌────────────────────────────┐    │
│  │ 🔍 البحث...                │    │
│  └────────────────────────────┘    │
├────────────────────────────────────┤
│ Quick Actions Row:                 │
│  [🏠 للبيع] [🏢 للإيجار] [🧭 استكشف]│
├────────────────────────────────────┤
│ "عقارات مميزة" (section header)    │
│ Horizontal ListView of PropertyCard│
├────────────────────────────────────┤
│ "عقارات جديدة" (section header)    │
│ Horizontal ListView of PropertyCard│
├────────────────────────────────────┤
│ "استكشف كل العقارات" (section)     │
│ Responsive Grid View of PropertyCard│
├────────────────────────────────────┤
│ WajhatakBottomNavBar               │
└────────────────────────────────────┘
```

## Widgets الرئيسية
- `HomeScreen extends ConsumerWidget`
- `WajhatakHeader extends PreferredSizeWidget`
- `Container` (hero gradient card)
- `ListView.horizontal` (featured + new sections)
- `GridView.builder` (all properties)
- `PropertyCard` (in both list and grid)
- `LuxAsyncView` for each section data

## API
- `ref.watch(propertiesProvider)` → List<LuxProperty>
- `ref.watch(propertySearchProvider(PropertyQuery(transactionType: 'sale')))`
- `ref.watch(propertySearchProvider(PropertyQuery(transactionType: 'rent')))`

## التنقل
- تكب البحث → `Navigator.push(ExploreScreen())` + `exploreSearchProvider.setSearch(...)`
- تكب PropertyCard → `Navigator.push(PropertyDetailScreen(id))`
- Quick action → نفس `ExploreScreen` مع filter

---

# 3. ExploreScreen (explore_screen.dart — 320 سطر)

## الهدف
البحث والاستكشاف مع فلترة وتبديل عرض (شبكة/خريطة).

## الترتيب على الشاشة

```
┌────────────────────────────────────┐
│ WajhatakHeader                     │
├────────────────────────────────────┤
│ Row:                               │
│  ┌───────────────────────────┐ 🗺️ │
│  │ TextField: البحث...       │    │
│  └───────────────────────────┘    │
├────────────────────────────────────┤
│ ChoiceChips:                      │
│  [الكل] [للبيع] [للإيجار]        │
├────────────────────────────────────┤
│ (Grid view)                       │
│ Responsive GridView of PropertyCard│
│  ┌────────┐ ┌────────┐           │
│  │ Card 1 │ │ Card 2 │           │
│  └────────┘ └────────┘           │
│  ┌────────┐ ┌────────┐           │
│  │ Card 3 │ │ Card 4 │           │
│  └────────┘ └────────┘           │
├────────────────────────────────────┤
│ WajhatakBottomNavBar               │
└────────────────────────────────────┘
```

## Widgets الرئيسية
- `ExploreScreen extends ConsumerStatefulWidget`
- `TextField` (controller + onChanged → setSearch)
- `ChoiceChip` (transaction type filter)
- `GridView.builder` (responsive)
- `IconButton` map toggle → `Navigator.push(PropertyMapScreen())`

## الـ State
```dart
final TextEditingController _searchController;
final ValueNotifier<String?> _transactionType; // sale / rent
```

## API
```dart
// Query built from current state
final query = PropertyQuery(
  search: _searchController.text,
  transactionType: _transactionType.value,
);
ref.watch(propertySearchProvider(query));
```

## ملاحظة عن search history
- `exploreSearchProvider` في providers.dart يحفظ آخر بحث
- Search history يظهر في SettingsScreen
- يُحفظ في `appSettingsProvider` (SharedPreferences)

---

# 4. PropertyDetailScreen (property_detail_screen.dart — 662 سطر)

## الهدف
عرض جميع تفاصيل العقار: صور، معلومات، ميزات، وكيل، موقع، واتخاذ إجراء.

## الترتيب على الشاشة

```
┌────────────────────────────────────┐
│ ← AppBar (back + share/badge)      │
├────────────────────────────────────┤
│ Image Gallery (PageView)           │
│  ┌──────────────────────────┐      │
│  │  [Property Image 1]      │      │
│  └──────────────────────────┘      │
│  Indicator dots (PageController)   │
├────────────────────────────────────┤
│ Title: اسم العقار                   │
│ Price + Currency + Transaction Badge│
│  "1,200,000 ر.ي"    [للبيع]        │
├────────────────────────────────────┤
│ Quick Facts Row:                   │
│  [🛏️ X غرف] [🚿 X حمام] [🚗 X مواقف]│
├────────────────────────────────────┤
│ "الوصف" section                    │
│ Multiline description text         │
├────────────────────────────────────┤
│ "المميزات" section                 │
│ Wrap of Feature Chips (مكيف، مفروش...)│
├────────────────────────────────────┤
│ Agent Card (horizontal)            │
│  [Avatar] [Name + Badge Agent]     │
│  [⭐ Rating (5.0)] [Reviews (12)]   │
│  [📞 Phone]                        │
├────────────────────────────────────┤
│ "الموقع" section                   │
│  [📍 Riyadh, Al-Malqa]             │
│  FlutterMap (image)                │
├────────────────────────────────────┤
│ Bottom Navigation Bar:             │
│  [♥ حفظ]  [💬 رسالة]  [📅 معاينة] │
└────────────────────────────────────┘
```

## Widgets الرئيسية
- `PropertyDetailScreen extends ConsumerWidget`
- `PageView` + `PageController` (gallery with 5s auto-play)
- `CachedNetworkImage` for each image
- `Container` (facts row)
- `Wrap` (feature chips)
- `AgentCard` (avatar + name + rating)
- `FlutterMap` (immobile map preview)
- `WajhatakBottomNavBar` (custom bottom actions)

## API
```dart
final detail = ref.watch(propertyDetailProvider(propertyId));
// -> LuxProperty with all fields loaded
```

## الأحداث
- 🌟 Heart: `toggleFavorite(context, ref, property)`
- 💬 Message: `Navigator.push(ChatScreen(conversationId or property))`
- 📅 Viewing: `showViewingSheet(context, property)`
- 🗺️ Map: `Navigator.push(PropertyMapScreen(properties))`

---

# 5. CreateListingScreen (create_listing_screen.dart — 854 سطر)

## الهدف
نموذج إنشاء عقار جديد — أكبر شاشة في المشروع.

## الترتيب على الشاشة

```
┌────────────────────────────────────┐
│ ← AppBar "إضافة عقار"              │
├────────────────────────────────────┤
│ SingleChildScrollView:             │
├────────────────────────────────────┤
│ SECTION: معلومات أساسية            │
│   TextFormField: عنوان العقار *    │
│   TextFormField: وصف العقار *      │
│   Dropdown: نوع العقار *           │
│   SegmentedButton: نوع المعاملة *  │
│     [بيع] [إيجار]                  │
├────────────────────────────────────┤
│ SECTION: السعر والعملة              │
│   TextFormField: السعر *           │
│   Dropdown: العملة (من currenciesProvider)│
├────────────────────────────────────┤
│ SECTION: التفاصيل                   │
│   Dropdown: عدد غرف النوم           │
│   Dropdown: عدد الحمامات            │
│   Dropdown: مواقف السيارات          │
│   SwitchListTile: مفروش             │
│   SwitchListTile: جديد              │
├────────────────────────────────────┤
│ SECTION: الموقع                     │
│   Dropdown: الدولة                  │
│   Dropdown: المنطقة (من country)    │
│   Dropdown: المدينة (من region)     │
│   Dropdown: الحي (من city)          │
│   TextFormField: اسم الحي *         │
│   TextFormField: العنوان *          │
├────────────────────────────────────┤
│ SECTION: المميزات                   │
│   FilterChip list (من featuresProvider)│
│   Wrap of selected chips            │
├────────────────────────────────────┤
│ SECTION: الصور (اختياري)            │
│   GridView of selected images       │
│   [+ إضافة صورة] button             │
│   (ImagePicker: max 12)             │
├────────────────────────────────────┤
│ TextButton: "إضافة العقار" (full width)│
└────────────────────────────────────┘
```

## الـ State
```dart
final GlobalKey<FormState> _formKey;
final _titleController, _descriptionController, _priceController, etc.;
List<File> _images;
int? _selectedCountryId, _selectedRegionId, _selectedCityId, _selectedAreaId;
String? _selectedTypeId;
Set<int> _selectedFeatures;
```

## API Calls (on submit)
1. `currenciesProvider` — للعملات (with Currency.fallback)
2. `countriesProvider`, `regionsProvider(countryId)`, `citiesProvider(regionId)`, `areasProvider(cityId)`
3. `propertyTypesProvider` — أنواع العقارات
4. `featuresProvider` — المميزات
5. On submit:
```dart
await ref.read(propertyRepositoryProvider).create(data: {
  'title': ...,
  'description': ...,
  'property_type_id': _selectedTypeId,
  'transaction_type': ...,
  'price': ...,
  'currency': ...,
  'bedrooms': ...,
  'bathrooms': ...,
  'parking_spaces': ...,
  'is_furnished': ...,
  'is_new': ...,
  'feature_ids': _selectedFeatures,
  'location': {
    'country_id': ..., 'region_id': ..., 'city_id': ..., 'area_id': ...,
    'city': ..., 'district': ..., 'address': ...
  },
}, images: _images);
```
- **Images compressed** via ImageCompressor (max 1920px, JPEG 78, 600KB)

---

# 6. EditListingScreen (edit_listing_screen.dart — 633 سطر)

## الهدف
تعديل عقار موجود.

## الترتيب على الشاشة
مشابه لـ CreateListing إلا أنه pre-populated مع الحقول الحالية:
- كل الحقول معبأة من `LuxProperty`
- إضافة: section للصور الموجودة مع أزرار حذف/تعيين كغلاف
```dart
// Image management buttons
IconButton(delete image) → PropertyRepository.deleteImage(propertyId, imageId)
IconButton(set as cover) → PropertyRepository.setCoverImage(propertyId, imageId)
IconButton(add new image) → ImagePicker → PropertyRepository.uploadImage()
```

## API
```dart
ref.read(propertyRepositoryProvider).update(propertyId, data) // PATCH /properties/{id}
```

---

# 7. AgentDashboardScreen (agent_dashboard_screen.dart — 451 سطر)

## الهدف
لوحة عمل الوكيل.

## الترتيب على الشاشة

```
┌────────────────────────────────────┐
│ ← AppBar "لوحة الوكيل" + FAB(+)    │
├────────────────────────────────────┤
│ Welcome Card (emerald gradient)    │
│  "مرحباً، [name]!"                 │
│  "هذا نظرة عامة على عملك العقاري"  │
├────────────────────────────────────┤
│ Stats Row:                         │
│  [📋 X إعلان] [💬 X محادثة]       │
│  [📅 X طلب معاينة]                │
├────────────────────────────────────┤
│ "خصائصي" section header            │
│ GridView of PropertyCard           │
│  (each with edit/delete menu)      │
├────────────────────────────────────┤
│ WajhatakBottomNavBar                │
└────────────────────────────────────┘
```

## API
- `myListingsProvider` → List<LuxProperty>
- `viewingRequestsProvider` → List<ViewingRequestItem>
- `conversationsProvider` → List<ConversationItem>

## الأحداث
- خصائصي → tap → `PropertyDetailScreen`
- ثلاث نقاط (edit/delete) → `EditListingScreen` or confirm delete
- FAB + → `CreateListingScreen`
- Viewing requests summary → `ViewingRequestsScreen`

---

# 8. ChatScreen (chat_screen.dart — 564 سطر)

## الهدف
محادثة 1:1 بين العميل والوكيل مع بطاقات عقار.

## الترتيب على الشاشة

```
┌────────────────────────────────────┐
│ ← AppBar "[Agent Name]"            │
├────────────────────────────────────┤
│ ListView (reversed) of messages    │
│                                    │
│  [PropertyCard message]            │  ← ما يميز هذا المشروع
│  ┌──────────────────────────┐      │
│  │ [Prop Image] Title        │     │
│  │ Price     [للبيع] [فتح]   │     │
│  └──────────────────────────┘      │
│                                    │
│  [Received: "مرحبا، هل العقار متاح؟"]│
│                                    │
│  [Sent: "نعم متاح، تفضل"]          │
│                                    │
├────────────────────────────────────┤
│ Input Row:                         │
│  [TextField: اكتب رسالة...]     [إرسال]│
└────────────────────────────────────┘
```

## الـ State
```dart
final TextEditingController _inputController;
bool _isSending = false;  // double submit protection
Timer? _refreshTimer;     // poll new messages
```

## API
- `messagesProvider(conversationId)` → List<ChatMessage>
- Auto-refresh: `Timer.periodic(Duration(seconds: 5), _refresh)` → `ChatMessagesNotifier.refresh()`
- Send: `ChatMessagesNotifier.send(body)`
  - optimistic: message appears instantly in list
  - then background refresh confirms

## Property Card Messages (ميزة متميزة)
```dart
// When conversation is created, first message is type='property'
// with property card UI
ChatMessageProperty card in ChatScreen
// Shows: Image + Title + Price + "فتح التفاصيل" button
// Tapping → Navigator.push(PropertyDetailScreen(propertyId))
```

---

# 9. MessagesScreen (messages_screen.dart — 217 سطر)

## الهدف
قائمة المحادثات.

## الترتيب على الشاشة

```
┌────────────────────────────────────┐
│ WajhatakHeader "الرسائل"           │
├────────────────────────────────────┤
│ ListView of ConversationItem:      │
│  [Avatar] AgentName        [⏰]    │
│  [Last message preview]  [Badge]   │
│  [Agent avatar circle]             │
│  (unread count red badge)          │
├────────────────────────────────────┤
│ WajhatakBottomNavBar                │
└────────────────────────────────────┘
```

## API
- `conversationsProvider` → List<ConversationItem>

## التنقل
- tap → `Navigator.push(ChatScreen(conversation))`

---

# 10. SavedScreen (saved_screen.dart — 139 سطر)

## الهدف
شاشة العقارات المحفوظة (المفضلة).

## الترتيب على الشاشة

```
┌────────────────────────────────────┐
│ WajhatakHeader "المحفوظات"         │
├────────────────────────────────────┤
│ (If not logged in):                │
│   EmptyState:                      │
│     [❤️ icon]                      │
│     "سجّل الدخول لحفظ العقارات"    │
│     TextButton: تسجيل الدخول       │
├────────────────────────────────────┤
│ (If logged in + empty):            │
│   EmptyState: "لم تحفظ عقارات بعد" │
├────────────────────────────────────┤
│ (If logged in + data):             │
│   Responsive GridView of PropertyCard│
├────────────────────────────────────┤
│ WajhatakBottomNavBar                │
└────────────────────────────────────┘
```

## API
- `favoritesProvider` → List<LuxProperty>
- Auth-gated: `_readForSession(ref, [], () => repo.favorites())`

---

# 11. AccountScreen (account_screen.dart — 262 سطر)

## الهدف
مركز حساب المستخدم.

## الترتيب على الشاشة

```
┌────────────────────────────────────┐
│ WajhatakHeader "حسابي"             │
├────────────────────────────────────┤
│ Profile Card:                      │
│  [Avatar] [Name]                   │
│  [Email/Phone]                     │
│  [Role Badge: عميل/وكيل]           │
├────────────────────────────────────┤
│ "إحصائيتي" quick actions grid:     │
│  [📅 طلباتي] [🔔 إشعاراتي]         │
│  [🛠️ وكلتي]  [⚙️ إعدادات]         │
├────────────────────────────────────┤
│ Profile/تصحيح: [✏️ تعديل الملف]    │
├────────────────────────────────────┤
│ Logout: [تسجيل الخروج]             │
└────────────────────────────────────┘
```

## API / التنقل
- يسحب الجلسة من `sessionProvider`
- Quick actions → Navigate to respective screens
- Logout → `confirmLogout(context, ref)`

---

# 12. ProfileScreen (profile_screen.dart — 357 سطر)

## الهدف
تعديل الملف الشخصي.

## الترتيب على الشاشة

```
┌────────────────────────────────────┐
│ ← AppBar "تعديل الملف الشخصي"      │
├────────────────────────────────────┤
│ [Avatar image]                     │
│ [📷 تغيير الصورة] (ImagePicker)    │
├────────────────────────────────────┤
│ TextFormField: الاسم               │
│ TextFormField: البريد الإلكتروني   │
│   (read-only — يُحدد في التسجيل)   │
│ TextFormField: رقم الجوال          │
├────────────────────────────────────┤
│ TextButton: حفظ التغييرات          │
└────────────────────────────────────┘
```

## API
- `sessionProvider.uploadAvatar(path)` → POST /me/avatar (compressed 512px, 256KB)
- `sessionProvider.updateProfile(name, phone)` → PATCH /me
- Both update `TokenStore.saveUser(user)`

---

# 13. SettingsScreen (settings_screen.dart — 235 سطر)

## الهدف
إعدادات التطبيق.

## الترتيب على الشاشة

```
┌────────────────────────────────────┐
│ ← AppBar "الإعدادات"               │
├────────────────────────────────────┤
│ Appearance:                        │
│  [🎨 المظهر]                       │
│    (System/Light/Dark — Segmented) │
├────────────────────────────────────┤
│ Notifications:                     │
│  Switch: رسائل              ✔      │
│  Switch: طلبات المعاينة     ✔     │
│  Switch: تحديثات العقارات   ✔     │
├────────────────────────────────────┤
│ Privacy:                           │
│  [🔒 تسجيل الخروج]                │
├────────────────────────────────────┤
│ About:                             │
│  [ℹ️ عن التطبيق]                   │
│  [📄 الترخيص]                     │
└────────────────────────────────────┘
```

## API / State
- `themeModeProvider` — حفظ في `SharedPreferencesAsync`
- Notification switches → `appSettingsProvider` (SharedPreferences)

---

# 14. NotificationsScreen (notifications_screen.dart — 175 سطر)

## الهدف
قائمة الإشعارات.

## الترتيب على الشاشة

```
┌────────────────────────────────────┐
│ ← AppBar "الإشعارات"               │
├────────────────────────────────────┤
│ ListView of LuxNotification:       │
│  [ActorAvatar]                     │
│  [Title: "رسالة جديدة"]            │
│  [Message text]                    │
│  [Relative time: "قبل 5 دقائق"]    │
│  (unread: bold + colored icon)     │
│  (read: grayed out)                │
├────────────────────────────────────┤
│ WajhatakBottomNavBar                │
└────────────────────────────────────┘
```

## API
- `notificationsProvider` → List<LuxNotification>
- `NotificationRepository.markNotificationRead(id)` → POST `/notifications/%id/read`

## التنقل (إشعار → شاشة)
```dart
// notification_navigation.dart
// kind == 'message_received' → ChatScreen
// kind == 'viewing_request_created/updated' → ViewingRequestsScreen
// kind contains 'property' → PropertyDetailScreen
```

---

# 15. ViewingRequestsScreen (viewing_requests_screen.dart — 218 سطر)

## الهدف
إدارة طلبات المعاينة (للمستخدم + للوكيل).

## الترتيب على الشاشة

```
┌────────────────────────────────────┐
│ ← AppBar "طلبات المعاينة"          │
├────────────────────────────────────┤
│ TabBar: [قيد الانتظار] [الكل]      │
├────────────────────────────────────┤
│ (Pending tab: only pending)        │
│ (All tab: all statuses)            │
├────────────────────────────────────┤
│ ListView of ViewingRequestItem:    │
│  [📅 Date] [⏰ Time]               │
│  [🏠 Property Title]               │
│  [Status Badge: pending/confirmed...]│
│  [Notes]                           │
│  (if agent): [✔ قبول] [✖ رفض]     │
│  (if client): [✖ إلغاء]            │
├────────────────────────────────────┤
│ WajhatakBottomNavBar                │
└────────────────────────────────────┘
```

## API
- `viewingRequestsProvider` → List<ViewingRequestItem>
- `ViewingRequestRepository.updateStatus(id, status)` → PATCH `/viewing-requests/%id`

## Role-based Actions
```dart
// Agent:
//   confirmed → ViewingRequestStatus.confirmed
//   rejected  → ViewingRequestStatus.rejected
//   completed → ViewingRequestStatus.completed
// Client:
//   cancelled → ViewingRequestStatus.cancelled
```

---

# 16. AppShell (app_shell.dart — 241 سطر)

## الهدف
الهيكل الرئيسي — يدير bottom nav + drawer + FAB + الإشعارات.

## الترتيب على الشاشة

```
┌────────────────────────────────────┐
│ (Selected screen content)          │
│  Home / Explore / Saved / Messages /│
│  Account                           │
├────────────────────────────────────┤
│ (FAB if agent — "＋ إضافة عقار")   │
├────────────────────────────────────┤
│ Drawer (from hamburger)            │
├────────────────────────────────────┤
│ WajhatakBottomNavBar (5 items)     │
└────────────────────────────────────┘
```

## الـ State
```dart
int _currentIndex = 0;
// Manage via AppShellState
```

## الإشعارات المحلية
```dart
// AppShell initState:
LuxNotificationService().initialize()
  .then((_) => LuxNotificationService().listenForNotifications(...))
  // Poll every 25s for unread count
  Timer.periodic(Duration(seconds: 25), (timer) {
    // fetch unread count from notificationsProvider
  });
```

## التنقل
- Bottom nav index → PageView/IndexedStack of 5 screens
- Drawer items → `Navigator.pushReplacement` (switch tabs)
- FAB → `CreateListingScreen` (if agent)
