# Documentation Index — خريطة الـ 50 مرحلة

فهرس المستندات. كل مرحلة من مراحل التحضير مرتبطة بالمستند الذي يغطيها في `documentation/`.

## المستندات

| الملف | الحجم | الغرض |
|-------|-------|-------|
| `DEFENSE_GUIDE.md` | 122 KB | الوثيقة الرئيسية — 37 قسماً شاملاً (الهندسة، المصادقة، قاعدة البيانات، الأسئلة، العرض، الـ checklist) |
| `SCREEN_ANALYSIS.md` | 33 KB | تحليل كل شاشة: layout مادة، Widgets، الـ State، الـ API |
| `PROJECT_INVENTORY.md` | 14 KB | جرد كل ملف Flutter و Laravel مع الوظائف والأسطر |
| `MUST_KNOW_FILES.md` | 12 KB | الملفات التي يجب أن تحفظها + deep-dive قاعدة البيانات |
| `API_DOCUMENTATION.md` | 5 KB | عقد الـ REST API الكامل |
| `DEPLOYMENT_AND_OPERATION.md` | 5 KB | النشر والتشغيل (PHP 8.3, MySQL, InfinityFree, artisan) |
| `WIDGET_DICTIONARY.md` | 21 KB | قاموس Widgets + Code Walkthrough للـ Core Files + Code Flows |
| `CRASH_COURSE.md` | 16 KB | تعليم من الصفر + أمثلة فعلية من الكود |
| `TESTING_AND_FUTURE.md` | 14 KB | الاختبار، خارطة الطريق، نقاط الضعف والتحسين |

---

## خريطة الـ 50 مرحلة → المستند

### المرحلة 1-10: التحليل الأساسي

| # | المرحلة | أين تجدها |
|---|---------|-----------|
| 1 | بنية المشروع العامة | DEFENSE_GUIDE §1, §5 |
| 2 | جرد الملفات | PROJECT_INVENTORY (كل ملف) |
| 3 | Requirements | DEFENSE_GUIDE §2 |
| 4 | الميزات | DEFENSE_GUIDE §3 |
| 5 | المستخدمون والأدوار | DEFENSE_GUIDE §4 |
| 6 | النشر والتشغيل | DEPLOYMENT_AND_OPERATION |
| 7 | قاعدة البيانات | DEFENSE_GUIDE §17-18 + MUST_KNOW deep-dive |
| 8 | Widgets | WIDGET_DICTIONARY القسم 1 |
| 9 | الشاشات | SCREEN_ANALYSIS + DEFENSE_GUIDE §9 |
| 10 | التنقل | DEFENSE_GUIDE §12 |

### المرحلة 11-20: Flutter

| # | المرحلة | أين تجدها |
|---|---------|-----------|
| 11 | إدارة الحالة (Riverpod) | DEFENSE_GUIDE §13 + CRASH_COURSE §5 |
| 12 | API client | WIDGET_DICTIONARY القسم 3 + DEFENSE_GUIDE §25 |
| 13 | النماذج | CRASH_COURSE §9 + SCREEN_ANALYSIS models |
| 14 | الـ models (JSON) | CRASH_COURSE §9 |
| 15 | المصادقة (Flutter) | DEFENSE_GUIDE §15 + WIDGET_DICTIONARY Flow 1 |
| 16 | الـ widgets الرئيسية | WIDGET_DICTIONARY §1-2 |
| 17 | Detailed screens | SCREEN_ANALYSIS |
| 18 | النوافذ والـ sheets | SCREEN_ANALYSIS flow sheets |
| 19 | الـ state محلي | WIDGET_DICTIONARY §2 |
| 20 | الـ props-answers | DEFENSE_GUIDE §34 (Flutter questions 11-30) |

### المرحلة 21-30: Laravel

| # | المرحلة | أين تجدها |
|---|---------|-----------|
| 21 | Controllers | DEFENSE_GUIDE §11 + PROJECT_INVENTORY Laravel |
| 22 | Models/Relationships | DEFENSE_GUIDE §18 + MUST_KNOW |
| 23 | Migration | MUST_KNOW Database Deep-Dive |
| 24 | Authentication server | DEFENSE_GUIDE §15 |
| 25 | Authorization (Policy/Spatie) | DEFENSE_GUIDE §16 |
| 26 | Validation (Form Requests) | DEFENSE_GUIDE §30 |
| 27 | Resources | PROJECT_INVENTORY Resources |
| 28 | Middleware | DEFENSE_GUIDE §16 + PROJECT_INVENTORY |
| 29 | Notifications | DEFENSE_GUIDE §15/24 |
| 30 | Data flow تفصيلي | TESTING_AND_FUTURE + CRASH_COURSE §11-13 |

### المرحلة 31-40: الميزات الرئيسية

| # | المرحلة | أين تجدها |
|---|---------|-----------|
| 31 | Code Flow (login/register/create/search/favorite) | WIDGET_DICTIONARY القسم 4 |
| 32 | Search & Filtering | DEFENSE_GUIDE §20 |
| 33 | Favorites | DEFENSE_GUIDE §21 |
| 34 | Image Upload | DEFENSE_GUIDE §22 |
| 35 | Property Management | DEFENSE_GUIDE §19 |
| 36 | Chat & محادثات | DEFENSE_GUIDE §24 (Scenario 2) |
| 37 | Viewing Requests | DEFENSE_GUIDE §24 (Scenario 3) |
| 38 | Notifications | DEFENSE_GUIDE §24 (Scenario 5) |
| 39 | Profile | DEFENSE_GUIDE §23 |
| 40 | End-to-End Workflows | DEFENSE_GUIDE §24 (كل السيناريوهات) |

### المرحلة 41-50: العمق التقني والحماية

| # | المرحلة | أين تجدها |
|---|---------|-----------|
| 41 | Security بكل تفاصيلها | DEFENSE_GUIDE §29 |
| 42 | Performance | DEFENSE_GUIDE §32 |
| 43 | Error Handling | DEFENSE_GUIDE §31 |
| 44 | Stake التكرار / Issues | DEFENSE_GUIDE §29 Security Issues |
| 45 | Crash Course (من الصفر) | CRASH_COURSE (كل المراحل) |
| 46 | Responsive / Multi-platform | TESTING_AND_FUTURE §Responsive |
| 47 | Testing | TESTING_AND_FUTURE §tests |
| 48 | تنسيقات الملفات / Deployment | DEPLOYMENT_AND_OPERATION |
| 49 | Glossary | DEFENSE_GUIDE §33 |
| 50 | أمر Defense أخير | DEFENSE_GUIDE §34-37 + TESTING_AND_FUTURE weaknesses |

---

## كيف تجهّز للمناقشة خلال 24 ساعة

1. **ساعة 1:** DEFENSE_GUIDE §1-5 (الفكرة، الهندسة، المستخدمون)
2. **ساعة 2:** DEFENSE_GUIDE §17-18 + MUST_KNOW (قاعدة البيانات)
3. **ساعة 3:** DEFENSE_GUIDE §15-16 (المصادقة والتفويض) + CRASH_COURSE §15-16
4. **ساعة 4:** WIDGET_DICTIONARY §3-4 (Code Flow)
5. **ساعة 5:** DEFENSE_GUIDE §34 (150 Question Bank — بالتقسيم الذكي)
6. **ساعة 6:** DEFENSE_GUIDE §35 (أسئلة الفخ) + §36 (Script) + §37 (Checklist)
7. **ساعة 7:** SCREEN_ANALYSIS (الشاشات بدقة)
8. **ساعة 8 (قبل النوم):** CRASH_COURSE (قراءة سريعة)

## آخر ليلة — راجِع فقط

- `MUST_KNOW_FILES.md` (افتح الملفات بنفسك)
- `TESTING_AND_FUTURE.md` (نقاط الضعف + الإجابات الجاهزة)
- `DEFENSE_GUIDE.md §37` (الـ checklist النهائي)

## شيء أخير

المرحلة الفارقة الحقيقية: افتح `mobile/lib/state/providers.dart` و`backend/app/Http/Controllers/Api/V1/PropertyController.php` — هذان الملفان هما عقل النظام. إن عرفتهم، فإن أي سؤال سِنصل إليه.

*— جاهز للمناقشة. بالتوفيق.*