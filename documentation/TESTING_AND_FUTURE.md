# Testing Strategy — Wajhatak

استراتيجية الاختبار الفعلية في المشروع + نصائح للعرض أمام اللجنة.

## ما يوجد فعلاً (فحص الملفات)

| الملف | الأسطر | يغطي |
|-------|--------|------|
| `mobile/test/domain_models_test.dart` | 255 | Currency model, formatMoney (تنسيق العملات العربية/السعودية), LuxProperty, LuxUser, PropertyQuery, PropertyLocation, ConversationItem, multi-locale |
| `mobile/test/models_test.dart` | 37 | LuxUser cast + LuxProperty البنائي |
| `mobile/test/preview_fixtures_test.dart` | 32 | تحميل ui_preview.json والتأكد أنه يطبّق على LuxProperty.fromJson |
| `mobile/test/property_card_test.dart` | 140 | PropertyCard render + favorite callback + carousel pages |
| `mobile/test/repro_circular_test.dart` | 91 | حل مشكلة circular dependency في إدارة الحالة |
| `mobile/test/rv_isolation_test.dart` | 35 | Riverpod isolation — التحقق من أن التحديثات لا تتسرب |
| `mobile/test/verify_challenge_solver.dart` | 20 | تحقق يدوي من حل تحدي InfinityFree |

## أنواع الاختبار في المشروع

1. **Unit tests** — النماذج والتنسيق (`domain_models_test.dart`)
   - Currency parsing مع fallback catalogue
   - formatMoney مع رموز عربية (ر.ي، ر.س، $)
   - LuxProperty/LuxUser fromJson مع قيم افتراضية آمنة
   - PropertyQuery إلى معلمات URL

2. **Widget tests** — `property_card_test.dart`
   - يعرض السعر، العنوان، الصور
   - حالات fav/unfav مع callbacks
   - PageView carousel تحرك

3. **Integration/live checks** — `verify_challenge_solver.dart`
   - التحقق اليدوي من Solver لدى استضافة InfinityFree

## كيف تشغّل

```bash
cd mobile
flutter test
```

## ما لا يوجد حاليًا — (وكن صادقاً أمام اللجنة)

- **لا توجد feature tests** لمصادقة Riverpod مع Fake API
- **لا يوجد coverage** (التغطية التقريبية لنماذج+fomat)
- **لا توجد اختبارات Laravel** (Pest/PHPUnit) — الزمن لم يسمح

## إجابات جاهزة للجنة

**سؤال: لماذا لا توجد اختبارات أكبر؟**
"ركزت على اختبار قطع المنطق الحساس: تنسيق العملات (UTC الصعب مع الأرقام العربية)، نماذج JSON (حماية من تغيّر شكل الـ API)، وتطبيق Carousel نفسها. لم أصل لاختبارات Laravel بسبب الزمن، لكنني وفّرت للمشغل أداة linting و فحص autoload."

**سؤال: ماذا ستضيف لو زاد الوقت؟**
1. فئة `ApiFake` تمشي في كل repositories وتختبر تدفقات (login → properties → chat)
2. اختبارات widgets للشاشات الكبيرة (Home/Explore) مع golden tests
3. PHPUnit / Pest لـ applyFilters و PropertyPolicy
4. CI/CD: GitHub Actions (lint + test) لكل PR

**سؤال: ما الفائدة التي رأيتها من teste property_card؟**
اكتشفت أن حالة الثبات تعتمد على `ref` — وعزلتها بشكل صحيح. لولا الاختبار لم تكن سلوك favorites سليم عند بدء التطبيق.

---

# Roadmap — خارطة الطريق

## أولويات حقيقية (حسب الحاجة الحقيقية للنسخة الحالية)

| الأولوية | الميزة | لماذا | الحل مخطط |
|----------|--------|-------|-----------|
| P0 | محادثات فورية (WebSocket) | حاليًا Polling كل 25 ثانية يعمل لكن فيه تأخير وشغل شبكة | Laravel Reverb / Pusher + flutter_ably |
| P0 | Reset password | لا يوجد حاليًا — منفذ للسؤال "كيف تسترد الحساب؟" | MailToken + Form |
| P1 | دفع عبر الإنترنت | معاملات قوية — المحادثة فقط لا تكفي | Stripe/PayTabs |
| P1 | Admin web dashboard | حاليًا يدوي فقط | Laravel Filament |
| P1 | Search بالذكاء | الفلاتر الحالية LIKE حرفي — نقص المرادفات | Meilisearch/Typesense + Arabic tokenizer |
| P1 | Notifications push (FCM) | حاليًا Local only (لا تعمل خارج التطبيق) | FCM + user_devices موجودة |
| P2 | React Native version | كتابة نفس التطبيق بأقل كود | Actually project is Flutter-only |
| P2 | Offline cache كامل | حاليًا user cache فقط | Hive/Drift + sync queue |
| P2 | i18n الإنجليزية | حاليًا العربية إلزامية | locale switch + AR/EN translations |
| P2 | Rating system و reviews | معلومات عن الوكيل | rating/reviews جدول + UI |
| P3 | Push Platform independent plugin | تقليل الحزم | — |
| P3 | Figma design tokens | توحيد الألوان عبر الفريق | Design tokens → WajhatakColors |

## أين يقف "الإشعارات الفورية" اليوم؟

- **Database notifications**: تعمل داخل التطبيق فقط (unread badge)
- **Local notifications**: تعمل داخل التطبيق (polling 25s) — تنبيه يظهر دون فتح التطبيق
- **Push (FCM/APNs)**: غير مفعل سراً — الـ table موجود لكن ما في hook

**الفجوة:** عند إغلاق التطبيق totally، لا تصلك إشعارات. هذا شيء يمكن ذكره شفافاً.

## أين يقف "البحث الذكي" اليوم؟

- **Full-text search**: غير مفعل — `where('title','like','%term%')` case-insensitive basic
- **لا توجد suggestions**: ليس في UI
- **الفرز**: published_at / price / hottest (المفضلة)

**التحسين:** Meilisearch يوفر ًArabic support (tashkeel, stop words, synonyms) — هذا تحسين cần thời gian.

---

# Weaknesses & Improvement Areas — (كن صادقاً لكن وازن)

> اللجان تحترم الوعي الذاتي أكثر من ادعاء الكمال.

## 1. سلبيات يجب أن تقرّ بها بثقة

| النقطة | الوضع الحالي | الخطة |
|--------|--------------|-------|
| Push notifications غير مفعّلة | داخل التطبيق فقط | ربط FCM في `user_devices` |
| Reset password غير موجود | — | إضافة flow |
| WebSocket vs polling | Polling كل 25 ثانية | Reverb |
| Admin dashboard بدون UI | artisan command فقط | Filament |
| Search non-Arabic-tuned | LIKE + English fallback | tokenizer |
| لا يوجد ضغط JSON response | Laravel default | gzip / Response facade |
| Rate limiting غير جاهز للتقسيط الكامل | throttle على auth فقط | تأكيد throttling لكل الكتابة |
| لا يوجد "إجمالي" insurance/حجز | لا يوجد نظام حجز فعلي | منحدر لاحق |

## 2. نقاط قوة يجب أن تبرز (للتوازن)

- **SoftDeletes** — أمان حذف العقارات
- **Optimistic UI** — استجابة فورية للمفضلة
- **Sensitive data في SecureStorage** — token لن يخزن في shared_preferences
- **Throttling + circuit breaker** — حماية من الـ spam/الفشل
- **AES-128-CBC solver** — حل تحدي الاستضافة الفريد (ميزة تقنية مميزة)
- **Localizations ar** — عربية متكاملة (RTL كامل)
- **Multi-tenancy roles** — admin/agent/user مع permissions

## 3. "ماذا لم تنجزه؟" — أجوبة جاهزة

| السؤال | الإجابة المثالية |
|--------|------------------|
| "لماذا لا يوجد WebSocket realtime؟" | "نعم، polling هو حل مؤقت. في نسخة الإنتاج سأستبدله بـ Reverb — لكن الـ architecture جاهز (interface على notifications)." |
| "لماذا لا يوجد admin UI؟" | "أدرت الخصوصية والأمان أولاً: عبر artisan command يمنح access محدد بـ IP و middleware. الـ UI لوحة قادمة." |
| "لماذا البحث العربية ضعيف؟" | "هذا تحسين مؤجل — للحلول العربية مثل Meilisearch Arabic analyzer يحتاج تخصيص. الأساس موجود." |
| "كيف تتعامل مع حساب مخترق؟" | "عبر reminder_token (remember me) من جانب، و password policy (mixed case + numbers). إضافة OTP قادمة." |

## 4. مخاطر يجب أن تدركها (وللمناقشة)

- **بدون rate limiting قوي على كل API** قد يهاجم بالـ brute force — الحل: sanctum بمراقبة IP
- **الإشعارات تعتمد على Application polling** — لو أغلقت التطبيق لن يصل إشعار خروج الحساب
- **الـ storage محلي فقط** — لا يوجد redundancy للصور إن حذفت من الاستضافة
- **بدون email verification** — المستخدمين بلا تأكيد بريد (تحديدًا لـ register تم تفعيله لكن مع مرونة)

---

# Responsive & Multi-Platform

## كيف يتكيّف الواجهة مع أحجام الشاشات

### Breakpoints (core/utils/responsive.dart)

| النوع | القيمة | يعمل |
|-------|--------|------|
| Mobile | <= 600dp | الافتراضي — عمودي واحد |
| Tablet | > 600dp | شبكات متعددة (2-3 أعمدة) |
| Desktop | > 1024dp | Layout كامل مع فلاتر جانبية |

### سلوك الأجهزة اللوحية

- HomeScreen: grid يستخدم 2 أو 3 أعمدة بواسطة `LayoutBuilder` + `SliverGridDelegateWithMaxCrossAxisExtent`
- PropertyCard: يملأ العرض
- ExploreScreen: الفلاتر في bottom sheet يتغير إلى side panel (في الإصدارات الأكبر)

### تحديات التوافق

- **Text overflow في RTL** — تم معالجته بـ `TextOverflow.ellipsis` + `maxLines` في PropertyCard و MessagesScreen
- **صورة عريضة** — `BoxFit.cover` مع `aspectRatio: 1.5` يتكيف

### Web/Desktop Support

المشروع يستهدف Mobile أولاً (Android + iOS). لكن:
- Flutter يدعم Web و Desktop تلقائياً من نفس الكود
- بعض الحزم تتطلب إعدادات إضافية (flutter_secure_storage على web يحتاج شرط)
- لا يوجد حاليًا تكوين Web **deploy** — Mobile is the target

## الإجابة المثالية عن "هل تطبيقك يدعم أجهزة متعددة؟"

"المهندس يعمل على Android و iOS عبر كود واحد (من Flutter). الـ layouts مصممة responsive: البطاقات والشبكات تتكيف مع العرض عبر LayoutBuilder و SliverGridDelegate. أما Web/Desktop فلم نستهدفها في هذه النسخة — لكنها قابلة للتفعيل لأنه نفس الكود."

## الإجابة عن "كيف تختبر على أجهزة مختلفة؟"

- **هذا غير موجود في النص**: لا يوجد explicit device tests. لكن responsive يتم عبر relative units (MediaQuery, Expanded, Flex) — نقل الكود تلقائياً.

---

*القائمة موسعة — أنت الآن تعرف ما يجب معرفته: الاختبار، المستقبل، نقاط الضعف، والتوافق.*