# LUX Living REST API Contract

## القاعدة العامة

تبدأ كل المسارات بـ `/api/v1`. تستعمل الاستجابات JSON مع `Accept: application/json`. تتطلب الموارد الخاصة رأس `Authorization: Bearer <token>` الصادر من Laravel Sanctum. لا يقبل أي مسار خاص معرف مستخدم لتبديل ملكية المورد؛ تُستخرج هوية المستخدم من الرمز فقط.

| المجال | المسار | الطريقة | الوصول |
|---|---|---|---|
| المصادقة | `/auth/register` | POST | عام |
| المصادقة | `/auth/login` | POST | عام ومحدد المعدل |
| المصادقة | `/auth/logout` | POST | مصادق |
| الحساب | `/me` | GET/PATCH | مصادق؛ المالك فقط |
| العقارات | `/properties` | GET | عام؛ المنشور فقط |
| العقارات | `/properties/{property}` | GET | عام للمنشور؛ المالك/المدير للحالات الأخرى |
| العقارات | `/properties` | POST | وكيل مخول |
| العقارات | `/properties/{property}` | PATCH/DELETE | مالك العقار أو مدير مخول |
| المفضلة | `/favorites` | GET/POST | مصادق؛ المستخدم الحالي فقط |
| المفضلة | `/favorites/{property}` | DELETE | مصادق؛ المستخدم الحالي فقط |
| الوكلاء | `/agents` و`/agents/{agent}` | GET | عام |
| المحادثات | `/conversations` | GET/POST | مصادق؛ أحد طرفي المحادثة فقط |
| الرسائل | `/conversations/{conversation}/messages` | GET/POST | مصادق؛ أحد طرفي المحادثة فقط |
| المعاينات | `/viewing-requests` | GET/POST | مصادق؛ العميل الحالي أو الوكيل المختص فقط |
| المعاينات | `/viewing-requests/{request}` | PATCH | العميل للإلغاء أو الوكيل لتحديث الحالة |
| الإشعارات | `/notifications` | GET | مصادق؛ المستخدم الحالي فقط |

## البحث والترقيم

يقبل `GET /properties` معاملات اختيارية تشمل `q`, `city`, `district`, `property_type`, `transaction_type`, `min_price`, `max_price`, `min_area`, `bedrooms`, `bathrooms`, `parking_spaces`, `is_furnished`, `is_new`, `is_featured`, `sort`, و`page`. ترد القوائم في مورد Laravel مرقّم من الشكل التالي:

```json
{
  "data": [],
  "links": {"first": null, "last": null, "prev": null, "next": null},
  "meta": {"current_page": 1, "per_page": 15, "total": 0}
}
```

## أمثلة أخطاء موحدة

| الحالة | الكود | الدلالة |
|---|---:|---|
| حقول غير صحيحة | 422 | تحتوي `errors` على الحقول ورسائلها. |
| لا يوجد رمز أو رمز غير صالح | 401 | تنتقل الواجهة إلى المصادقة بعد تنظيف الحالة الخاصة. |
| دور غير مصرح أو مورد ليس للمستخدم | 403 | لا تكشف الاستجابة بيانات المورد. |
| مورد غير موجود أو غير منشور للزائر | 404 | لا تسرب الحالة الداخلية للعقار. |
| حد معدل متجاوز | 429 | تعيد الواجهة خيار إعادة المحاولة بصورة مفهومة. |

## الاتصال بالتطبيق

يتلقى Flutter عنوان الخادم فقط عبر `--dart-define=LUX_API_BASE_URL=...`. يستعمل المحاكي Android العنوان `http://10.0.2.2:8000/api/v1` في التطوير المحلي، ويستعمل الجهاز الحقيقي عنوان LAN واضحًا، بينما يجب أن يستعمل الإصدار الإنتاجي نطاق HTTPS.

## العملات (Currencies)

### `GET /api/v1/currencies`

كتالوج العملات المدعومة (data-driven من config/currencies.php). عام — لا يتطلب مصادقة.

**الاستجابة:**
```json
{
  "data": [
    {
      "code": "YER",
      "name_ar": "ريال يمني",
      "name_en": "Yemeni Rial",
      "symbol_ar": "ر.ي",
      "symbol_en": "YR",
      "flag": "🇾🇪",
      "decimals": 0,
      "is_default": true
    },
    { "code": "SAR", "name_ar": "ريال سعودي", "symbol_ar": "ر.س", "flag": "🇸🇦", "is_default": false },
    { "code": "USD", "name_ar": "دولار أمريكي", "symbol_ar": "$", "flag": "🇺🇸", "decimals": 2, "is_default": false }
  ]
}
```

**ملاحظات:**
- العملة الافتراضية للعقارات الجديدة هي `YER` (على مستوى قاعدة البيانات).
- قيمة مقبولة فقط: `YER | SAR | USD` في إنشاء/تعديل العقارات، وأي عملة أخرى تُرفض بـ422.
- إنشاء عقار بدون عملة يطبّق الافتراضية `YER` تلقائيًا وتعاد في الاستجابة.
