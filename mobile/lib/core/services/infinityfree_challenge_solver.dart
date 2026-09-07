import 'dart:async';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:encrypt/encrypt.dart';

/// يحل تحدّي الحماية الأمني لتطبيق InfinityFree.
///
/// تستخدم استضافة InfinityFree المجانية نظام «Browser Security» الذي يعترض
/// كل الطلبات غير المتصفحية (كالـ API) ويعيد صفحة HTML مشفّرة تحتوي على
/// تحدّي AES (متغيرات `a`/`b`/`c`) لتوليد كوكي `__test`. بما أن عميلنا
/// (Dio) لا ينفّذ JavaScript، نحسب قيمة الكوكي بأنفسنا عبر فك تشفير
/// AES-128-CBC (بدون حشو) تمامًا كما تفعل الصفحة، ثم نرسل الكوكي مع
/// كل طلب ليتجاوز التحدّي ويتلقّى السيرفر استجابة JSON الحقيقية.
///
/// ملاحظة: هذه القيمة تُدوّر من طرف الاستضافة، لذلك نعيد الحساب عند
/// انتهاء الصلاحية أو عند رفض الخادم للكوكي (اكتشاف تحدٍّ جديد).
class InfinityFreeChallengeSolver {
  InfinityFreeChallengeSolver({String? probeBaseUrl})
      : _probeBaseUrl = probeBaseUrl ?? 'https://wajhatak.infinityfree.io/api/v1',
        _probeDio = Dio(
          BaseOptions(
            // يجب إرسال نفس الـ User-Agent في طلب الحل وفي طلبات الـ API
            // لأن السيرفر يُصدِر تحدّيًا مختلفًا (قيمة `c`) لكل User-Agent.
            headers: const {'User-Agent': InfinityFreeChallengeSolver.userAgent},
            responseType: ResponseType.plain,
            connectTimeout: const Duration(seconds: 12),
            receiveTimeout: const Duration(seconds: 20),
          ),
        );

  /// User-Agent واحد تُرسله كل طلبات الـ API وطلب حلّ التحدّي معًا،
  /// وإلا يرفض السيرفر الكوكي المحسوب لتطابقٍ غير مكتمل مَع الـ UA.
  static const String userAgent =
      'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 '
      'Chrome/120.0.0.0 Mobile Safari/537.36';

  /// عنوان جذر الـ API الذي نستخدمه للتجسس على صفحة التحدّي.
  final String _probeBaseUrl;

  /// إعداد Dio منفصل (بلا معترضات التطبيق) لإجراء عملية «التعرف» فقط.
  final Dio _probeDio;

  /// الكوكي `__test` المحسوب حاليًا، إن وُجد.
  String? _cookie;

  /// وقت حساب الكوكي (الخادم يُدوّر القيم بمرور الوقت).
  DateTime? _solvedAt;

  /// مدة صلاحية محلية تحفظنا من الانهيار عند دوران معاملات الخادم.
  static const _localityCache = Duration(hours: 4);

  int _solveAttempts = 0;
  static const _maxAttempts = 3;

  /// طلب «التعرف» الجاري حاليًا (قفل أحادي الرحل): إن كان عدة طلبات تحتاج
  /// حلّ التحدّي معًا، نتشارك عملية واحدة بدل إرسال عدة أوامر probe تسبب
  /// ضغطًا على الاستضافة وتظهر كأنها فحص آلي.
  Future<String?>? _inflightSolve;

  String? get cookie => _cookie;

  /// هل الكوكي الحالي ما يزال صالحًا ضمن نافذتنا الزمنية المحلية.
  bool get _isCookieFresh {
    if (_cookie == null || _solvedAt == null) return false;
    return DateTime.now().difference(_solvedAt!) < _localityCache;
  }

  /// يُبطِل الكوكي الحالي ليجبر على الحساب من جديد.
  void invalidate() {
    _cookie = null;
    _solvedAt = null;
  }

  /// يضمن وجود كوكي صالح: إن لم يوجد، يحسبه من صفحة التحدّي.
  ///
  /// يعيد الكوكي الناتج أو `null` إذا تعذّر الحل (لم يعد التحدّي قائمًا
  /// فيجعل الطلبات تمرّ مباشرة كما هي، أو خطأ شبكة مؤقت).
  Future<String?> ensureCookie() async {
    if (_isCookieFresh) return _cookie;
    // قفل أحادي الرحل يمنع إرسال عدة طلبات حلّ دفعةً واحدة في البرد.
    if (_inflightSolve != null) return _inflightSolve!;
    final f = solve();
    return _inflightSolve = f.whenComplete(() => _inflightSolve = null);
  }

  /// يحسب كوكيًا جديدًا فورًا متجاهلًا الصلاحية المحلية — يُستخدم عندما
  /// يردّ الخادم بتحدٍّ رغم وجود كوكي مخزّن (دوران المعاملات).
  Future<String?> forceSolve() {
    // قفل أحادي الرحل: لا نرسل أكثر من عملية حلّ واحدة في اللحظة مهما كان
    // عدد الطلبات التي تحتاجه معًا (يمنع اندفاع طلبات الـ probe نحو الخادم).
    if (_inflightSolve != null) return _inflightSolve!;
    final f = _solveInternal();
    return _inflightSolve = f.whenComplete(() => _inflightSolve = null);
  }

  Future<String?> _solveInternal() async {
    invalidate();
    return solve();
  }

  /// يحاول حساب كوكي ``__test` من صفحة التحدّي الجديدة.
  Future<String?> solve() async {
    if (_solveAttempts >= _maxAttempts) {
      // حماية من الدوران الخبيث — نتوقف ولا نكبس الخادم.
      _solveAttempts = 0;
      return null;
    }
    _solveAttempts++;

    try {
      final response = await _probeDio.get<dynamic>(
        _probeBaseUrl,
        options: Options(responseType: ResponseType.plain),
      );
      return _cookieFromChallengeHtml(response.data);
    } on DioException {
      _solveAttempts = 0;
      return null;
    }
  }

  /// يستخرج قيم `a`/`b`/`c` من خلاصة HTML ويفك تشفيرها إلى الكوكي.
  String? _cookieFromChallengeHtml(Object? raw) {
    if (raw is! String || !raw.contains('toNumbers(')) {
      // إما أن الاستضافة توقفت عن إصدار التحدّي (يمكننا المرور مباشرة)
      // أو استجابة غير متوقعة.
      _solveAttempts = 0;
      return null;
    }
    final matches = RegExp(
      r'toNumbers\("([0-9a-f]{32,})"',
    ).allMatches(raw).toList();
    if (matches.length < 3) {
      _solveAttempts = 0;
      return null;
    }
    final aHex = matches[0].group(1)!;
    final bHex = matches[1].group(1)!;
    final cHex = matches[2].group(1)!;

    try {
      final key = Key(_hexToBytes(aHex));
      final iv = IV(_hexToBytes(bHex));
      final cipher = _hexToBytes(cHex);
      final encrypter = Encrypter(
        AES(key, mode: AESMode.cbc, padding: null),
      );
      final decrypted = encrypter.decryptBytes(Encrypted(cipher), iv: iv);
      final cookieValue = _bytesToHex(decrypted).toLowerCase();
      final cookie = '__test=$cookieValue';
      _cookie = cookie;
      _solvedAt = DateTime.now();
      _solveAttempts = 0;
      return cookie;
    } on Object {
      _solveAttempts = 0;
      return null;
    }
  }

  Uint8List _hexToBytes(String hex) {
    final buffer = List<int>.generate(hex.length ~/ 2, (i) {
      return int.parse(hex.substring(i * 2, i * 2 + 2), radix: 16);
    });
    return Uint8List.fromList(buffer);
  }

  String _bytesToHex(List<int> bytes) {
    final buffer = StringBuffer();
    for (final b in bytes) {
      if (b < 16) buffer.write('0');
      buffer.write(b.toRadixString(16));
    }
    return buffer.toString();
  }
}